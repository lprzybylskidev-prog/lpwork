<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Http\Routing\FastRouteDispatcherFactory;
use LPwork\Http\Routing\RouteLoader;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms route dispatcher cache.
 */
class RouteCacheCommand extends Command
{
    /**
     * @var CacheConfiguration
     */
    private CacheConfiguration $configuration;

    /**
     * @var CacheFactory
     */
    private CacheFactory $cacheFactory;

    /**
     * @var RouteLoader
     */
    private RouteLoader $routeLoader;

    /**
     * @var FastRouteDispatcherFactory
     */
    private FastRouteDispatcherFactory $dispatcherFactory;

    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $redisConnections;

    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $databaseConnections;

    /**
     * @param CacheConfiguration         $configuration
     * @param CacheFactory               $cacheFactory
     * @param RouteLoader                $routeLoader
     * @param FastRouteDispatcherFactory $dispatcherFactory
     * @param RedisConnectionManager     $redisConnections
     * @param DatabaseConnectionManager  $databaseConnections
     */
    public function __construct(
        CacheConfiguration $configuration,
        CacheFactory $cacheFactory,
        RouteLoader $routeLoader,
        FastRouteDispatcherFactory $dispatcherFactory,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
    ) {
        parent::__construct();
        $this->configuration = $configuration;
        $this->cacheFactory = $cacheFactory;
        $this->routeLoader = $routeLoader;
        $this->dispatcherFactory = $dispatcherFactory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:route:cache")
            ->setAliases(["route:cache"])
            ->setDescription("Warm route dispatcher cache");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $routing = $this->configuration->routingCache();
        $enabled = (bool) ($routing["enabled"] ?? false);

        if (!$enabled) {
            $output->writeln(
                "<comment>Route cache is disabled (ROUTE_CACHE_ENABLED=false).</comment>",
            );

            return Command::SUCCESS;
        }

        $poolName = (string) ($routing["pool"] ?? "filesystem");
        $key = (string) ($routing["key"] ?? "routes:dispatcher");

        $pool = $this->cacheFactory->createPool(
            $poolName,
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );

        $routes = $this->routeLoader->load();
        $data = $this->dispatcherFactory->generateData($routes);

        $pool->deleteItem($key);
        $item = $pool->getItem($key);
        $item->set($data);
        $pool->save($item);

        $output->writeln(
            \sprintf(
                "<info>Route cache warmed (pool: %s, key: %s).</info>",
                $poolName,
                $key,
            ),
        );

        return Command::SUCCESS;
    }
}
