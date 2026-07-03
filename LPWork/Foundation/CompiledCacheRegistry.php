<?php

declare(strict_types=1);

namespace LPWork\Foundation;

use function array_key_exists;
use function ksort;

use LPWork\Foundation\Contracts\CompiledCache;
use LPWork\Foundation\Exceptions\DuplicateCompiledCacheException;

/**
 * Stores and resolves compiled cache registry registrations.
 */
final class CompiledCacheRegistry
{
    /**
     * @var array<string, CompiledCache>
     */
    private array $caches = [];

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(CompiledCache $cache): void
    {
        $name = $cache->name();

        if (array_key_exists($name, $this->caches) || array_key_exists($name, $this->aliases)) {
            throw DuplicateCompiledCacheException::forName($name);
        }

        foreach ($cache->aliases() as $alias) {
            if (array_key_exists($alias, $this->caches) || array_key_exists($alias, $this->aliases)) {
                throw DuplicateCompiledCacheException::forName($alias);
            }
        }

        $this->caches[$name] = $cache;

        foreach ($cache->aliases() as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    /**
     * Performs the find operation.
     */
    public function find(string $nameOrAlias): ?CompiledCache
    {
        if (array_key_exists($nameOrAlias, $this->caches)) {
            return $this->caches[$nameOrAlias];
        }

        if (array_key_exists($nameOrAlias, $this->aliases)) {
            return $this->caches[$this->aliases[$nameOrAlias]];
        }

        return null;
    }

    /**
     * @return array<string, CompiledCache>
     */
    public function all(): array
    {
        $caches = $this->caches;
        ksort($caches);

        return $caches;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->all());
    }
}
