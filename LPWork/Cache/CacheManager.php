<?php

declare(strict_types=1);

namespace LPWork\Cache;

use LPWork\Cache\Exceptions\InvalidCacheConfigException;
use LPWork\Cache\Exceptions\InvalidCacheStoreException;
use LPWork\Cache\Exceptions\MissingCacheConfigException;
use LPWork\Config\NamedDriverConfig;
use LPWork\Config\NamedDriverConfigFactory;

/**
 * Resolves configured cache stores and exposes store metadata.
 */
final class CacheManager
{
    /**
     * @var array<string, CacheStore>
     */
    private array $stores = [];

    private NamedDriverConfig $storeConfig;

    private CacheDriverFactory $driverFactory;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly string $basePath,
        ?CacheDriverFactory $driverFactory = null,
        private readonly ?CacheDebugCollector $debugCollector = null,
    ) {
        $this->storeConfig = $this->storeConfig($config);
        $this->driverFactory = $driverFactory ?? new CacheDriverFactory($this->basePath);
    }

    /**
     * Returns the configured default cache store.
     */
    public function default(): CacheStore
    {
        return $this->store($this->defaultStoreName());
    }

    /**
     * Returns the configured cache store name used when no store is requested explicitly.
     */
    public function defaultStoreName(): string
    {
        return $this->storeConfig->defaultName();
    }

    /**
     * Returns a named cache store, creating and caching it on first use.
     */
    public function store(string $name): CacheStore
    {
        if (array_key_exists($name, $this->stores)) {
            return $this->stores[$name];
        }

        $config = $this->storeConfig->entry($name, static fn(string $name): InvalidCacheStoreException => new InvalidCacheStoreException($name));

        $this->stores[$name] = new CacheStore(
            name: $name,
            driver: $this->driverFactory->create($config, $this->storeConfig->entryKey($name)),
            debugCollector: $this->debugCollector,
        );

        return $this->stores[$name];
    }

    /**
     * Returns all configured cache store names.
     *
     * @return list<string>
     */
    public function storeNames(): array
    {
        return $this->storeConfig->names();
    }

    /**
     * Returns the configured driver type for a named cache store.
     */
    public function storeDriverName(string $name): string
    {
        $config = $this->storeConfig->entry($name, static fn(string $name): InvalidCacheStoreException => new InvalidCacheStoreException($name));
        $driver = $config['driver'] ?? null;

        return is_string($driver) && $driver !== '' ? $driver : 'unknown';
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function storeConfig(array $config): NamedDriverConfig
    {
        return new NamedDriverConfigFactory()->create(
            config: $config,
            entriesKey: 'stores',
            missingException: static fn(string $key): MissingCacheConfigException => new MissingCacheConfigException($key),
            invalidException: static fn(string $key): InvalidCacheConfigException => new InvalidCacheConfigException($key),
        );
    }
}
