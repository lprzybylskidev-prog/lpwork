<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;

use function preg_match;

use const PREG_OFFSET_CAPTURE;

use function preg_quote;
use function str_contains;
use function str_replace;
use function strlen;
use function strrpos;
use function substr;
use function var_export;

/**
 * Represents the provider file registrar framework component.
 */
final readonly class ProviderFileRegistrar
{
    /**
     * Creates a new ProviderFileRegistrar instance.
     */
    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(ProviderRegistration $registration, string $class, ?string $group): string
    {
        $providerPath = $this->app->basePath($registration->providerPath());

        if (!$this->files->isFile($providerPath)) {
            throw FileCreatorException::providerMissing($providerPath);
        }

        $contents = $this->files->read($providerPath);

        if (str_contains($contents, '\\' . $class . ';') || str_contains($contents, $class . '::class')) {
            return $providerPath;
        }

        $contents = $this->insertUse($contents, $class);
        $updated = match ($registration->style()) {
            ProviderRegistrationStyle::List => $this->insertIntoListMethod($contents, $registration->methodName(), $this->shortClass($class)),
            ProviderRegistrationStyle::Grouped => $this->insertIntoGroupedMethod(
                $contents,
                $registration->methodName(),
                $this->shortClass($class),
                $group ?? $registration->defaultGroup() ?? 'default',
            ),
        };

        if ($updated === null) {
            throw FileCreatorException::cannotUpdateProvider($providerPath, $registration->methodName());
        }

        $this->files->write($providerPath, $updated);

        return $providerPath;
    }

    private function insertUse(string $contents, string $class): string
    {
        $use = 'use ' . $class . ';';

        if (str_contains($contents, $use)) {
            return $contents;
        }

        if (preg_match('/^(use [^;]+;\n)(?!use )/m', $contents, $match, PREG_OFFSET_CAPTURE) === 1) {
            $position = $match[0][1] + strlen($match[0][0]);

            return substr($contents, 0, $position) . $use . "\n" . substr($contents, $position);
        }

        if (preg_match('/^namespace [^;]+;\n/m', $contents, $match, PREG_OFFSET_CAPTURE) === 1) {
            $position = $match[0][1] + strlen($match[0][0]);

            return substr($contents, 0, $position) . "\n" . $use . "\n" . substr($contents, $position);
        }

        return $contents;
    }

    private function insertIntoListMethod(string $contents, string $method, string $className): ?string
    {
        $pattern = '/((?:public|protected) function ' . preg_quote($method, '/') . '\(\): array\s*\{\s*return \[\n)(.*?)(\s*\];\s*\})/s';
        $entry = '            ' . $className . "::class,\n";

        if (preg_match($pattern, $contents, $matches) !== 1) {
            return $this->insertIntoEmptyListMethod($contents, $method, $entry);
        }

        return str_replace($matches[0], $matches[1] . $matches[2] . $entry . $matches[3], $contents);
    }

    private function insertIntoGroupedMethod(string $contents, string $method, string $className, string $group): ?string
    {
        $pattern = '/((?:public|protected) function ' . preg_quote($method, '/') . '\(\): array\s*\{\s*return \[\n)(.*?)(\s*\];\s*\})/s';

        if (preg_match($pattern, $contents, $matches) !== 1) {
            return $this->insertIntoEmptyListMethod(
                $contents,
                $method,
                '            ' . var_export($group, true) . " => [\n"
                    . '                ' . $className . "::class,\n"
                    . "            ],\n",
            );
        }

        $body = $matches[2];
        $groupPattern = '/(            ' . preg_quote(var_export($group, true), '/') . " => \\[\n)(.*?)(            \\],\n)/s";

        if (preg_match($groupPattern, $body, $groupMatches) === 1) {
            $groupBlock = $groupMatches[1] . $groupMatches[2] . '                ' . $className . "::class,\n" . $groupMatches[3];
            $body = str_replace($groupMatches[0], $groupBlock, $body);

            return str_replace($matches[0], $matches[1] . $body . $matches[3], $contents);
        }

        $body .= '            ' . var_export($group, true) . " => [\n";
        $body .= '                ' . $className . "::class,\n";
        $body .= "            ],\n";

        return str_replace($matches[0], $matches[1] . $body . $matches[3], $contents);
    }

    private function insertIntoEmptyListMethod(string $contents, string $method, string $entry): ?string
    {
        $pattern = '/((?:public|protected) function ' . preg_quote($method, '/') . '\(\): array\s*\{\s*)return \[\];(\s*\})/s';

        if (preg_match($pattern, $contents, $matches) !== 1) {
            return null;
        }

        return str_replace($matches[0], $matches[1] . "return [\n" . $entry . '        ];' . $matches[2], $contents);
    }

    private function shortClass(string $class): string
    {
        $position = strrpos($class, '\\');

        if ($position === false) {
            return $class;
        }

        return substr($class, $position + 1);
    }
}
