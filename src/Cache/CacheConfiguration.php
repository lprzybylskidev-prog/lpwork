<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Cache\Exception\CacheConfigurationException;
use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration holder for cache pools and routing/config cache options.
 */
final class CacheConfiguration
{
    use ConfigNormalizer;

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
     * @var array<string, mixed>
     */
    private array $translations;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->defaultPool = $this->stringVal(
            $config['default_pool'] ?? null,
            'cache.default_pool',
            'array',
            false,
        );
        $this->pools = (array) ($config['pools'] ?? []);
        $this->routing = (array) ($config['routing'] ?? []);
        $this->configCache = (array) ($config['config_cache'] ?? []);
        $this->translations = (array) ($config['translations'] ?? []);

        if ($this->pools !== [] && !isset($this->pools[$this->defaultPool])) {
            throw new CacheConfigurationException(
                \sprintf('Default cache pool "%s" is not defined.', $this->defaultPool),
            );
        }
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

    /**
     * @return array<string, mixed>
     */
    public function translations(): array
    {
        return $this->translations;
    }

    /**
     * Returns all configured cache pools.
     *
     * @return array<string, array<string, mixed>>
     */
    public function pools(): array
    {
        return $this->pools;
    }
}
