<?php
declare(strict_types=1);

namespace LPwork\Cache\Contract;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Contract for resolving cache pools.
 */
interface CacheManagerInterface
{
    /**
     * @return CacheItemPoolInterface
     */
    public function defaultPool(): CacheItemPoolInterface;

    /**
     * @param string $name
     *
     * @return CacheItemPoolInterface
     */
    public function pool(string $name): CacheItemPoolInterface;

    /**
     * @return Psr16Cache
     */
    public function defaultSimpleCache(): Psr16Cache;

    /**
     * @param string $name
     *
     * @return Psr16Cache
     */
    public function simpleCache(string $name): Psr16Cache;
}
