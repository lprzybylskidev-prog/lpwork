<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Config\PhpConfigLoader;
use LPwork\Http\Routing\FastRouteDispatcherFactory;
use LPwork\Http\Routing\RouteLoader;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Handles warming/clearing of built-in cache consumers (routing, configuration).
 */
class CacheProvider implements CacheProviderInterface
{
    /**
     * @var CacheConfiguration
     */
    private CacheConfiguration $configuration;

    /**
     * @var RouteLoader
     */
    private RouteLoader $routeLoader;

    /**
     * @var FastRouteDispatcherFactory
     */
    private FastRouteDispatcherFactory $dispatcherFactory;

    /**
     * @var PhpConfigLoader
     */
    private PhpConfigLoader $configLoader;

    /**
     * @param CacheConfiguration         $configuration
     * @param RouteLoader                $routeLoader
     * @param FastRouteDispatcherFactory $dispatcherFactory
     * @param PhpConfigLoader            $configLoader
     */
    public function __construct(
        CacheConfiguration $configuration,
        RouteLoader $routeLoader,
        FastRouteDispatcherFactory $dispatcherFactory,
        PhpConfigLoader $configLoader,
    ) {
        $this->configuration = $configuration;
        $this->routeLoader = $routeLoader;
        $this->dispatcherFactory = $dispatcherFactory;
        $this->configLoader = $configLoader;
    }

    /**
     * @inheritDoc
     */
    public function warm(string $poolName, CacheItemPoolInterface $pool): void
    {
        $this->warmConfig($poolName, $pool);
        $this->warmRoutes($poolName, $pool);
    }

    /**
     * @inheritDoc
     */
    public function clear(string $poolName, CacheItemPoolInterface $pool): void
    {
        $this->clearConfig($poolName, $pool);
        $this->clearRoutes($poolName, $pool);
    }

    /**
     * @param string                 $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function warmConfig(string $poolName, CacheItemPoolInterface $pool): void
    {
        $configCache = $this->configuration->configCache();
        $enabled = (bool) ($configCache['enabled'] ?? false);
        $cachePool = (string) ($configCache['pool'] ?? 'filesystem');

        if (!$enabled || $cachePool !== $poolName) {
            return;
        }

        $key = (string) ($configCache['key'] ?? 'config:repository');
        $configs = $this->configLoader->loadDirectory(\dirname(__DIR__, 3) . '/config/configs');

        $item = $pool->getItem($key);
        $item->set($configs);
        $pool->save($item);
    }

    /**
     * @param string                 $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function warmRoutes(string $poolName, CacheItemPoolInterface $pool): void
    {
        $routingCache = $this->configuration->routingCache();
        $enabled = (bool) ($routingCache['enabled'] ?? false);
        $cachePool = (string) ($routingCache['pool'] ?? 'filesystem');

        if (!$enabled || $cachePool !== $poolName) {
            return;
        }

        $key = (string) ($routingCache['key'] ?? 'routes:dispatcher');
        $routes = $this->routeLoader->load();
        $data = $this->dispatcherFactory->generateData($routes);
        $item = $pool->getItem($key);
        $item->set($data);
        $pool->save($item);
    }

    /**
     * @param string                 $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function clearConfig(string $poolName, CacheItemPoolInterface $pool): void
    {
        $configCache = $this->configuration->configCache();
        $enabled = (bool) ($configCache['enabled'] ?? false);
        $cachePool = (string) ($configCache['pool'] ?? 'filesystem');

        if (!$enabled || $cachePool !== $poolName) {
            return;
        }

        $key = (string) ($configCache['key'] ?? 'config:repository');
        $pool->deleteItem($key);
    }

    /**
     * @param string                 $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function clearRoutes(string $poolName, CacheItemPoolInterface $pool): void
    {
        $routingCache = $this->configuration->routingCache();
        $enabled = (bool) ($routingCache['enabled'] ?? false);
        $cachePool = (string) ($routingCache['pool'] ?? 'filesystem');

        if (!$enabled || $cachePool !== $poolName) {
            return;
        }

        $key = (string) ($routingCache['key'] ?? 'routes:dispatcher');
        $pool->deleteItem($key);
    }
}
