<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Foundation\Contracts\ReadableCompiledCache;

/**
 * Represents the config compiled cache framework component.
 */
final readonly class ConfigCompiledCache implements ReadableCompiledCache
{
    /**
     * Creates a new ConfigCompiledCache instance.
     */
    public function __construct(
        private ConfigCache $cache,
        private ConfigCacheRebuilder $rebuilder,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'config';
    }

    /**
     * Returns label.
     */
    public function label(): string
    {
        return 'Configuration cache';
    }

    /**
     * Registers or stores aliases.
     */
    public function aliases(): array
    {
        return ['configuration', 'config:cache'];
    }

    /**
     * Reports whether exists.
     */
    public function exists(): bool
    {
        return $this->cache->exists();
    }

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->cache->path();
    }

    /**
     * Performs the rebuild operation.
     */
    public function rebuild(): void
    {
        $this->rebuilder->rebuild();
    }
}
