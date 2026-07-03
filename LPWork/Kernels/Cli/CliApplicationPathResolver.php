<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli;

use function dirname;
use function is_dir;
use function is_file;

use LPWork\Kernels\Cli\Exceptions\InvalidCliApplicationPathException;

use function realpath;
use function trim;

/**
 * Resolves cli application path resolver values into runtime objects.
 */
final readonly class CliApplicationPathResolver
{
    /**
     * Creates a new CliApplicationPathResolver instance.
     */
    public function __construct(
        private string $entrypointDirectory,
    ) {}

    /**
     * Resolves configured input into a runtime value.
     */
    public function resolve(?string $workingDirectory = null, ?string $configuredBasePath = null): string
    {
        if ($configuredBasePath !== null && trim($configuredBasePath) !== '') {
            return $this->existingDirectory($configuredBasePath, 'LPWORK_BASE_PATH');
        }

        if ($this->isApplicationRoot($this->entrypointDirectory)) {
            return $this->existingDirectory($this->entrypointDirectory, 'CLI entrypoint directory');
        }

        if ($workingDirectory !== null && trim($workingDirectory) !== '') {
            return $this->findFromWorkingDirectory($workingDirectory);
        }

        throw InvalidCliApplicationPathException::notFound($this->entrypointDirectory);
    }

    private function findFromWorkingDirectory(string $workingDirectory): string
    {
        $current = $this->existingDirectory($workingDirectory, 'current working directory');

        while (true) {
            if ($this->isApplicationRoot($current)) {
                return $current;
            }

            $parent = dirname($current);

            if ($parent === $current) {
                throw InvalidCliApplicationPathException::notFound($workingDirectory);
            }

            $current = $parent;
        }
    }

    private function isApplicationRoot(string $path): bool
    {
        return is_file($path . '/vendor/autoload.php') && is_dir($path . '/App');
    }

    private function existingDirectory(string $path, string $source): string
    {
        $resolved = realpath($path);

        if ($resolved === false || !is_dir($resolved)) {
            throw InvalidCliApplicationPathException::invalid($source, $path);
        }

        return $resolved;
    }
}
