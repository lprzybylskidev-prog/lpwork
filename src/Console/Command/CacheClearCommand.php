<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears cache pool contents.
 */
class CacheClearCommand extends Command
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
        $this->setName("lpwork:cache:clear")
            ->setAliases(["cache:clear"])
            ->setDescription("Clear cache pool")
            ->addArgument(
                "pool",
                InputArgument::OPTIONAL,
                "Cache pool name (defaults to CACHE_DEFAULT_POOL)",
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $poolName = (string) ($input->getArgument("pool") ?? "");

        if ($poolName === "") {
            $poolName = $this->configuration->defaultPool();
        }

        $pool = $this->cacheFactory->createPool(
            $poolName,
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );

        $pool->clear();
        $output->writeln(
            \sprintf('<info>Cache pool "%s" cleared.</info>', $poolName),
        );

        return Command::SUCCESS;
    }
}
