<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Cache\Exception\CacheConfigurationException;

/**
 * Typed configuration holder for cache pools and routing/config cache options.
 */
final class CacheConfiguration
{
    /**
     * @var string
     */
    private string $defaultPool;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $pools;

    /**
     * @var array<string, mixed>
     */
    private array $routing;

    /**
     * @var array<string, mixed>
     */
    private array $configCache;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->defaultPool = (string) ($config["default_pool"] ?? "array");
        $this->pools = (array) ($config["pools"] ?? []);
        $this->routing = (array) ($config["routing"] ?? []);
        $this->configCache = (array) ($config["config_cache"] ?? []);
    }

    /**
     * @return string
     */
    public function defaultPool(): string
    {
        return $this->defaultPool;
    }

    /**
     * @param string $name
     *
     * @return array<string, mixed>
     */
    public function pool(string $name): array
    {
        if (!isset($this->pools[$name])) {
            throw new CacheConfigurationException(
                \sprintf('Cache pool "%s" is not defined.', $name),
            );
        }

        return (array) $this->pools[$name];
    }

    /**
     * @return array<string, mixed>
     */
    public function routingCache(): array
    {
        return $this->routing;
    }

    /**
     * @return array<string, mixed>
     */
    public function configCache(): array
    {
        return $this->configCache;
    }
}
