<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Config\PhpConfigLoader;
use LPwork\Environment\Env;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms configuration cache.
 */
class ConfigCacheCommand extends Command
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
     * @var Env
     */
    private Env $env;

    /**
     * @param CacheConfiguration        $configuration
     * @param CacheFactory              $cacheFactory
     * @param RedisConnectionManager    $redisConnections
     * @param DatabaseConnectionManager $databaseConnections
     * @param Env                       $env
     */
    public function __construct(
        CacheConfiguration $configuration,
        CacheFactory $cacheFactory,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
        Env $env,
    ) {
        parent::__construct();
        $this->configuration = $configuration;
        $this->cacheFactory = $cacheFactory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
        $this->env = $env;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:config:cache")
            ->setAliases(["config:cache"])
            ->setDescription("Warm configuration cache");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $configCache = $this->configuration->configCache();
        $enabled = (bool) ($configCache["enabled"] ?? false);

        if (!$enabled) {
            $output->writeln(
                "<comment>Configuration cache is disabled (CONFIG_CACHE_ENABLED=false).</comment>",
            );

            return Command::SUCCESS;
        }

        $poolName = (string) ($configCache["pool"] ?? "filesystem");
        $key = (string) ($configCache["key"] ?? "config:repository");

        $pool = $this->cacheFactory->createPool(
            $poolName,
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );

        $loader = new PhpConfigLoader($this->env);
        $configs = $loader->loadDirectory(
            \dirname(__DIR__, 3) . "/config/configs",
        );

        $pool->deleteItem($key);
        $item = $pool->getItem($key);
        $item->set($configs);
        $pool->save($item);

        $output->writeln(
            \sprintf(
                "<info>Configuration cache warmed (pool: %s, key: %s).</info>",
                $poolName,
                $key,
            ),
        );

        return Command::SUCCESS;
    }
}
