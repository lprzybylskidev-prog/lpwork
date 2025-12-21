<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears route dispatcher cache entry.
 */
class RouteClearCommand extends Command
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
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $redisConnections;

    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $databaseConnections;

    /**
     * @param CacheConfiguration        $configuration
     * @param CacheFactory              $cacheFactory
     * @param RedisConnectionManager    $redisConnections
     * @param DatabaseConnectionManager $databaseConnections
     */
    public function __construct(
        CacheConfiguration $configuration,
        CacheFactory $cacheFactory,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
    ) {
        parent::__construct();
        $this->configuration = $configuration;
        $this->cacheFactory = $cacheFactory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:route:clear")
            ->setAliases(["route:clear"])
            ->setDescription("Clear route dispatcher cache entry");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $routing = $this->configuration->routingCache();
        $poolName = (string) ($routing["pool"] ?? "filesystem");
        $key = (string) ($routing["key"] ?? "routes:dispatcher");

        $pool = $this->cacheFactory->createPool(
            $poolName,
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );

        $pool->deleteItem($key);
        $output->writeln(
            \sprintf(
                "<info>Route cache cleared (pool: %s, key: %s).</info>",
                $poolName,
                $key,
            ),
        );

        return Command::SUCCESS;
    }
}
