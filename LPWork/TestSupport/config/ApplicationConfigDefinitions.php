<?php

declare(strict_types=1);

namespace Tests\support\config;

use App\Shared\Configs\CacheConfig;
use App\Shared\Configs\StorageConfig;
use LPWork\Config\ConfigSourceDefinitions;
use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Container\Container;

final class ApplicationConfigDefinitions
{
    public static function initStorage(): void
    {
        \LPWork\Config\Config::initDefinitions([
            new StorageConfig(),
        ]);
    }

    public static function initStorageAndCache(): void
    {
        \LPWork\Config\Config::initDefinitions(self::storageAndCache());
    }

    public static function registerStorageAndCacheSource(Container $container): void
    {
        $container->instance(ConfigSource::class, new ConfigSourceDefinitions(self::storageAndCache()));
    }

    /**
     * @return list<ConfigDefinition>
     */
    private static function storageAndCache(): array
    {
        return [
            new StorageConfig(),
            new CacheConfig(),
        ];
    }
}
