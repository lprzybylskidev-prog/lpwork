<?php
declare(strict_types=1);

namespace LPwork\Config;

use Psr\Cache\CacheItemPoolInterface;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Config\PhpConfigRepository;

/**
 * Wraps PhpConfigRepository with cache storage.
 */
class CachedConfigRepository implements ConfigRepositoryInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configs;

    /**
     * @var CacheItemPoolInterface
     */
    private CacheItemPoolInterface $cache;

    /**
     * @var string
     */
    private string $cacheKey;

    /**
     * @var PhpConfigRepository|null
     */
    private ?PhpConfigRepository $repository = null;

    /**
     * @param array<string, array<string, mixed>> $configs
     * @param CacheItemPoolInterface             $cache
     * @param string                             $cacheKey
     */
    public function __construct(array $configs, CacheItemPoolInterface $cache, string $cacheKey)
    {
        $this->configs = $configs;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed ...$default): mixed
    {
        return $this->loadCached()->get($key, ...$default);
    }

    /**
     * @inheritDoc
     */
    public function getString(string $key, ?string $default = null): string
    {
        return $this->loadCached()->getString($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function getInt(string $key, ?int $default = null): int
    {
        return $this->loadCached()->getInt($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function getFloat(string $key, ?float $default = null): float
    {
        return $this->loadCached()->getFloat($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        return $this->loadCached()->getBool($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->loadCached()->has($key);
    }

    /**
     * @return Contract\ConfigRepositoryInterface
     */
    private function loadCached(): Contract\ConfigRepositoryInterface
    {
        if ($this->repository !== null) {
            return $this->repository;
        }

        $item = $this->cache->getItem($this->cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            if (\is_array($cached)) {
                $this->repository = new PhpConfigRepository($cached);

                return $this->repository;
            }
        }

        $this->cache->deleteItem($this->cacheKey);
        $item = $this->cache->getItem($this->cacheKey);
        $item->set($this->configs);
        $this->cache->save($item);

        $this->repository = new PhpConfigRepository($this->configs);

        return $this->repository;
    }
}
