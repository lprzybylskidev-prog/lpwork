<?php

declare(strict_types=1);

namespace LPWork\View;

use LPWork\Filesystem\Exceptions\InvalidPathException;

use function trim;

/**
 * Stores and resolves view namespace registry registrations.
 */
final class ViewNamespaceRegistry
{
    /**
     * @var array<string, list<string>>
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

        $this->paths[$namespace] ??= [];
        $this->paths[$namespace][] = $path;
    }

    /**
     * @return list<string>
     */
    public function paths(string $namespace): array
    {
        return $this->paths[$namespace] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->paths;
    }
}
