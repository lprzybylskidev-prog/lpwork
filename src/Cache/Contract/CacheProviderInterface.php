<?php
declare(strict_types=1);

namespace LPwork\Cache\Contract;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Allows warming and clearing cache pools for application-specific scenarios.
 */
interface CacheProviderInterface
{
    /**
     * Warms the given cache pool (application-defined content).
     *
     * @param string                $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    public function warm(string $poolName, CacheItemPoolInterface $pool): void;

    /**
     * Clears entries related to the given cache pool (application-defined content).
     *
     * @param string                $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    public function clear(string $poolName, CacheItemPoolInterface $pool): void;
}
