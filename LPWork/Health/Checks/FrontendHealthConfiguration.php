<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use JsonException;
use LPWork\Filesystem\Filesystem;

/**
 * Represents the frontend health configuration framework component.
 */
final readonly class FrontendHealthConfiguration
{
    /**
     * Creates a new FrontendHealthConfiguration instance.
     */
    public function __construct(
        private string $basePath,
        private Filesystem $files,
    ) {}

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function missingFiles(array $paths): array
    {
        $missing = [];

        foreach ($paths as $path) {
            if (!$this->files->isFile($this->path($path))) {
                $missing[] = $path;
            }
        }

        return $missing;
    }

    /**
     * @param list<string> $scripts
     *
     * @return list<string>
     */
    public function missingScripts(array $scripts): array
    {
        $configured = $this->packageSection('scripts');
        $missing = [];

        foreach ($scripts as $script) {
            if (!array_key_exists($script, $configured)) {
                $missing[] = $script;
            }
        }

        return $missing;
    }

    /**
     * @param list<string> $dependencies
     *
     * @return list<string>
     */
    public function missingDevDependencies(array $dependencies): array
    {
        $configured = $this->packageSection('devDependencies');
        $missing = [];

        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency, $configured)) {
                $missing[] = $dependency;
            }
        }

        return $missing;
    }

    /**
     * Reports whether has file.
     */
    public function hasFile(string $path): bool
    {
        return $this->files->isFile($this->path($path));
    }

    /**
     * Builds or returns read.
     */
    public function read(string $path): string
    {
        return $this->files->read($this->path($path));
    }

    /**
     * Returns path.
     */
    public function path(string $path): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function packageSection(string $section): array
    {
        $package = $this->package();
        $value = $package[$section] ?? [];

        return $this->stringKeyedArray($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function package(): array
    {
        if (!$this->hasFile('package.json')) {
            return [];
        }

        try {
            $decoded = json_decode($this->read('package.json'), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return $this->stringKeyedArray($decoded);
    }

    /**
     * @return array<string, mixed>
     */
    private function stringKeyedArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
