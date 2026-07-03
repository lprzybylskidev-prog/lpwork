<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

use function array_filter;
use function array_slice;
use function count;
use function explode;
use function implode;

use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Foundation\Application;

use function preg_match;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Resolves file creator path resolver values into runtime objects.
 */
final readonly class FileCreatorPathResolver
{
    /**
     * Creates a new FileCreatorPathResolver instance.
     */
    public function __construct(
        private Application $app,
    ) {}

    /**
     * Resolves configured input into a runtime value.
     */
    public function resolve(FileCreatorDefinition $definition, string $name, ?string $path, ?string $namespace): ResolvedFile
    {
        $name = trim($name, "\\/ \t\n\r\0\x0B");

        if ($name === '') {
            throw FileCreatorException::missingName($definition->type());
        }

        $name = str_replace('\\', '/', $name);
        $segments = array_values(array_filter(explode('/', $name), static fn(string $segment): bool => $segment !== ''));

        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment) !== 1) {
                throw FileCreatorException::invalidName($name);
            }
        }

        $className = $this->className($segments[count($segments) - 1], $definition->suffix());
        $nestedDirectory = implode('/', array_slice($segments, 0, -1));
        $baseDirectory = $path === null || $path === '' ? $definition->defaultDirectory() : trim($path, '/');
        $relativeDirectory = $nestedDirectory === '' ? $baseDirectory : $baseDirectory . '/' . $nestedDirectory;
        $relativePath = $relativeDirectory . '/' . $className . '.php';
        $resolvedNamespace = $this->namespace($baseDirectory, $namespace);

        if ($nestedDirectory !== '') {
            $resolvedNamespace .= '\\' . str_replace('/', '\\', $nestedDirectory);
        }

        return new ResolvedFile(
            path: $this->app->basePath($relativePath),
            namespace: $resolvedNamespace,
            className: $className,
            class: $resolvedNamespace . '\\' . $className,
        );
    }

    private function className(string $name, string $suffix): string
    {
        if ($suffix !== '' && !str_ends_with($name, $suffix)) {
            return $name . $suffix;
        }

        return $name;
    }

    private function namespace(string $relativeDirectory, ?string $namespace): string
    {
        if ($namespace !== null && trim($namespace, '\\') !== '') {
            return trim($namespace, '\\');
        }

        $relativeDirectory = trim($relativeDirectory, '/');

        if ($relativeDirectory === 'App' || str_starts_with($relativeDirectory, 'App/')) {
            return str_replace('/', '\\', $relativeDirectory);
        }

        if ($relativeDirectory === 'LPWork' || str_starts_with($relativeDirectory, 'LPWork/')) {
            return str_replace('/', '\\', $relativeDirectory);
        }

        throw FileCreatorException::cannotInferNamespace($relativeDirectory);
    }
}
