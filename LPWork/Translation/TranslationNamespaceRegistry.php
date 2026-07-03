<?php

declare(strict_types=1);

namespace LPWork\Translation;

use LPWork\Filesystem\Exceptions\InvalidPathException;

use function trim;

/**
 * Stores and resolves translation namespace registry registrations.
 */
final class TranslationNamespaceRegistry
{
    /**
     * @var array<string, string>
     */
    private array $paths = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $namespace, string $path): void
    {
        $namespace = trim($namespace);
        $path = trim($path);

        if ($namespace === '' || $path === '') {
            throw InvalidPathException::empty();
        }

        $this->paths[$namespace] = $path;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->paths;
    }
}
