<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms cache pools (built-in routing/config and optional application provider).
 */
class CacheWarmCommand extends Command
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
     * @var CacheProviderInterface|null
     */
    private ?CacheProviderInterface $provider;

    /**
     * @param CacheConfiguration        $configuration
     * @param CacheFactory              $cacheFactory
     * @param RedisConnectionManager    $redisConnections
     * @param DatabaseConnectionManager $databaseConnections
     * @param CacheProviderInterface|null $provider
     */
    public function __construct(
        CacheConfiguration $configuration,
        CacheFactory $cacheFactory,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
        ?CacheProviderInterface $provider = null,
    ) {
        parent::__construct();
        $this->configuration = $configuration;
        $this->cacheFactory = $cacheFactory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:cache')
            ->setAliases(['cache:warm'])
            ->setDescription('Warm cache pools')
            ->addArgument(
                'pool',
                InputArgument::OPTIONAL,
                'Cache pool name (default if omitted, all when --all)',
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Warm all pools');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $poolArg = (string) ($input->getArgument('pool') ?? '');
        $all = (bool) $input->getOption('all');

        $pools = $this->resolvePools($poolArg, $all);

        foreach ($pools as $poolName) {
            $pool = $this->cacheFactory->createPool(
                $poolName,
                $this->configuration,
                $this->redisConnections,
                $this->databaseConnections,
            );

            if ($this->provider !== null) {
                $this->provider->warm($poolName, $pool);
            }

            $output->writeln(\sprintf('<info>Cache pool "%s" warmed.</info>', $poolName));
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $poolArg
     * @param bool   $all
     *
     * @return array<int, string>
     */
    private function resolvePools(string $poolArg, bool $all): array
    {
        if ($all) {
            return \array_keys($this->configuration->pools());
        }

        if ($poolArg !== '') {
            return [$poolArg];
        }

        return [$this->configuration->defaultPool()];
    }
}
