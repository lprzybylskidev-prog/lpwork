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
 * Clears configuration cache entry.
 */
class ConfigClearCommand extends Command
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
        $this->setName("lpwork:config:clear")
            ->setAliases(["config:clear"])
            ->setDescription("Clear configuration cache entry");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $configCache = $this->configuration->configCache();
        $poolName = (string) ($configCache["pool"] ?? "filesystem");
        $key = (string) ($configCache["key"] ?? "config:repository");

        $pool = $this->cacheFactory->createPool(
            $poolName,
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );

        $pool->deleteItem($key);
        $output->writeln(
            \sprintf(
                "<info>Configuration cache cleared (pool: %s, key: %s).</info>",
                $poolName,
                $key,
            ),
        );

        return Command::SUCCESS;
    }
}
