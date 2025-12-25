<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Cache\Exception\CacheConfigurationException;
use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration holder for cache pools and logical cache definitions.
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
     * @var array<string, array<string, mixed>>
     */
    private array $caches;

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
        $this->caches = (array) ($config['caches'] ?? []);

        // Backward compatibility: allow legacy keys if caches are not provided.
        if ($this->caches === []) {
            foreach (['config', 'routing', 'routes', 'translations'] as $legacyKey) {
                if (isset($config[$legacyKey])) {
                    $targetKey = $legacyKey === 'routing' ? 'routes' : $legacyKey;
                    $this->caches[$targetKey] = (array) $config[$legacyKey];
                }
            }

            if ($this->caches === []) {
                $this->caches = [
                    'config' => [],
                    'routes' => [],
                    'translations' => [],
                ];
            }
        }

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
     * @return array<string, array<string, mixed>>
     */
    public function caches(): array
    {
        return $this->caches;
    }

    /**
     * @return array<int, string>
     */
    public function cacheNames(): array
    {
        return \array_keys($this->caches);
    }

    /**
     * @param string $name
     *
     * @return array<string, mixed>
     */
    public function cache(string $name): array
    {
        if (!isset($this->caches[$name])) {
            throw new CacheConfigurationException(\sprintf('Cache "%s" is not defined.', $name));
        }

        return (array) $this->caches[$name];
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
        return $this->caches['routes'] ?? ($this->caches['routing'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function configCache(): array
    {
        return $this->caches['config'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function translations(): array
    {
        return $this->caches['translations'] ?? [];
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
