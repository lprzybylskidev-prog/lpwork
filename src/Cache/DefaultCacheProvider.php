<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Config\PhpConfigLoader;
use LPwork\Environment\Env;
use LPwork\Http\Routing\FastRouteDispatcherFactory;
use LPwork\Http\Routing\RouteLoader;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Handles warming/clearing of built-in cache consumers (routing, configuration).
 */
class DefaultCacheProvider implements CacheProviderInterface
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
     * @var Env
     */
    private Env $env;

    /**
     * @param CacheConfiguration         $configuration
     * @param RouteLoader                $routeLoader
     * @param FastRouteDispatcherFactory $dispatcherFactory
     * @param Env                        $env
     */
    public function __construct(
        CacheConfiguration $configuration,
        RouteLoader $routeLoader,
        FastRouteDispatcherFactory $dispatcherFactory,
        Env $env,
    ) {
        $this->configuration = $configuration;
        $this->routeLoader = $routeLoader;
        $this->dispatcherFactory = $dispatcherFactory;
        $this->env = $env;
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
     * @param string                $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function warmConfig(string $poolName, CacheItemPoolInterface $pool): void
    {
        $configCache = $this->configuration->configCache();
        $enabled = (bool) ($configCache['enabled'] ?? false);
        $targetPool = (string) ($configCache['pool'] ?? 'filesystem');

        if (!$enabled || $targetPool !== $poolName) {
            return;
        }

        $key = (string) ($configCache['key'] ?? 'config:repository');
        $loader = new PhpConfigLoader($this->env);
        $configs = $loader->loadDirectory(\dirname(__DIR__, 2) . '/config/configs');

        $pool->deleteItem($key);
        $item = $pool->getItem($key);
        $item->set($configs);
        $pool->save($item);
    }

    /**
     * @param string                $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function clearConfig(string $poolName, CacheItemPoolInterface $pool): void
    {
        $configCache = $this->configuration->configCache();
        $enabled = (bool) ($configCache['enabled'] ?? false);
        $targetPool = (string) ($configCache['pool'] ?? 'filesystem');

        if (!$enabled || $targetPool !== $poolName) {
            return;
        }

        $key = (string) ($configCache['key'] ?? 'config:repository');
        $pool->deleteItem($key);
    }

    /**
     * @param string                $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function warmRoutes(string $poolName, CacheItemPoolInterface $pool): void
    {
        $routing = $this->configuration->routingCache();
        $enabled = (bool) ($routing['enabled'] ?? false);
        $targetPool = (string) ($routing['pool'] ?? 'filesystem');

        if (!$enabled || $targetPool !== $poolName) {
            return;
        }

        $key = (string) ($routing['key'] ?? 'routes:dispatcher');
        $routes = $this->routeLoader->load();
        $data = $this->dispatcherFactory->generateData($routes);

        $pool->deleteItem($key);
        $item = $pool->getItem($key);
        $item->set($data);
        $pool->save($item);
    }

    /**
     * @param string                $poolName
     * @param CacheItemPoolInterface $pool
     *
     * @return void
     */
    private function clearRoutes(string $poolName, CacheItemPoolInterface $pool): void
    {
        $routing = $this->configuration->routingCache();
        $enabled = (bool) ($routing['enabled'] ?? false);
        $targetPool = (string) ($routing['pool'] ?? 'filesystem');

        if (!$enabled || $targetPool !== $poolName) {
            return;
        }

        $key = (string) ($routing['key'] ?? 'routes:dispatcher');
        $pool->deleteItem($key);
    }
}
