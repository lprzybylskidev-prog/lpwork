<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Contracts\ConfigSource;

/**
 * Represents the config cache rebuilder framework component.
 */
final readonly class ConfigCacheRebuilder
{
    /**
     * Creates a new ConfigCacheRebuilder instance.
     */
    public function __construct(
        private ConfigCache $cache,
        private ConfigSource $source,
    ) {}

    /**
     * Performs the rebuild operation.
     */
    public function rebuild(): void
    {
        $this->cache->clear();
        Config::reset();
        Config::initSource($this->source);
        $this->cache->write();
    }
}
