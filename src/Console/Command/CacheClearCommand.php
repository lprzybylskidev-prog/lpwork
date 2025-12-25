<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\Contract\CacheFactoryInterface;
use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears caches (config/routing/translations) or cache pools.
 */
class CacheClearCommand extends Command
{
    private CacheConfiguration $configuration;
    private CacheFactoryInterface $cacheFactory;
    private RedisConnectionManagerInterface $redisConnections;
    private DatabaseConnectionManagerInterface $databaseConnections;
    private ?CacheProviderInterface $provider;

    /**
     * @param CacheConfiguration $configuration
     * @param CacheFactoryInterface $cacheFactory
     * @param RedisConnectionManagerInterface $redisConnections
     * @param DatabaseConnectionManagerInterface $databaseConnections
     * @param CacheProviderInterface|null $provider
     */
    public function __construct(
        CacheConfiguration $configuration,
        CacheFactoryInterface $cacheFactory,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
        ?CacheProviderInterface $provider = null,
    ) {
        parent::__construct();
        $this->configuration = $configuration;
        $this->cacheFactory = $cacheFactory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
        $this->provider = $provider;
    }

    protected function configure(): void
    {
        $this->setName('lpwork:cache:clear')
            ->setAliases(['cache:clear'])
            ->setDescription('Clear caches (config/routes/translations/custom)')
            ->addArgument(
                'pool',
                InputArgument::OPTIONAL,
                'Cache name (config/routes/translations/custom; default is config, all when --all)',
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Clear all caches')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available caches');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $poolArg = (string) ($input->getArgument('pool') ?? '');
        $poolArg = $this->normalizeName($poolArg);
        $all = (bool) $input->getOption('all');
        $list = (bool) $input->getOption('list');

        if ($list) {
            $this->printPools($output);
            return Command::SUCCESS;
        }

        $targets = $this->resolveTargets($poolArg, $all, $output);
        if ($targets === []) {
            return Command::FAILURE;
        }

        foreach ($targets as $name) {
            if ($this->handleBuiltIn($name, $output)) {
                continue;
            }

            $cache = $this->configuration->cache($name);
            $poolName = (string) ($cache['pool'] ?? $this->configuration->defaultPool());
            $pool = $this->cacheFactory->createPool(
                $poolName,
                $this->configuration,
                $this->redisConnections,
                $this->databaseConnections,
            );

            $pool->clear();

            if ($this->provider !== null) {
                $this->provider->clear($name, $pool);
            }

            $output->writeln(\sprintf('<info>Cache "%s" cleared.</info>', $name));
        }

        return Command::SUCCESS;
    }

    /**
     * Clears built-in caches if name matches.
     *
     * @param string $name
     * @param OutputInterface $output
     *
     * @return bool
     */
    private function handleBuiltIn(string $name, OutputInterface $output): bool
    {
        if ($name === 'config') {
            $configCache = $this->configuration->cache('config');
            if (!(bool) ($configCache['enabled'] ?? false)) {
                $output->writeln('<comment>Configuration cache is disabled in settings.</comment>');
                return true;
            }
            $poolName = (string) ($configCache['pool'] ?? 'filesystem');
            $key = (string) ($configCache['key'] ?? 'configs');
            $pool = $this->cacheFactory->createPool(
                $poolName,
                $this->configuration,
                $this->redisConnections,
                $this->databaseConnections,
            );
            $pool->deleteItem($key);
            $output->writeln(
                \sprintf('<info>Configuration cache cleared (%s:%s).</info>', $poolName, $key),
            );
            return true;
        }

        if ($name === 'routes') {
            $routing = $this->configuration->cache('routes');
            if (!(bool) ($routing['enabled'] ?? false)) {
                $output->writeln('<comment>Routing cache is disabled in settings.</comment>');
                return true;
            }
            $poolName = (string) ($routing['pool'] ?? 'filesystem');
            $key = (string) ($routing['key'] ?? 'routes');
            $pool = $this->cacheFactory->createPool(
                $poolName,
                $this->configuration,
                $this->redisConnections,
                $this->databaseConnections,
            );
            $pool->deleteItem($key);
            $output->writeln(
                \sprintf('<info>Routing cache cleared (%s:%s).</info>', $poolName, $key),
            );
            return true;
        }

        if ($name === 'translations') {
            $translations = $this->configuration->cache('translations');
            if (!(bool) ($translations['enabled'] ?? true)) {
                $output->writeln('<comment>Translation cache is disabled in settings.</comment>');
                return true;
            }
            $poolName = (string) ($translations['pool'] ?? 'filesystem');
            $pool = $this->cacheFactory->createPool(
                $poolName,
                $this->configuration,
                $this->redisConnections,
                $this->databaseConnections,
            );
            $pool->clear();
            $output->writeln(
                \sprintf('<info>Translation cache cleared (pool %s).</info>', $poolName),
            );
            return true;
        }

        return false;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    private function printPools(OutputInterface $output): void
    {
        $output->writeln('<info>Available caches:</info>');
        foreach ($this->cacheNames() as $name) {
            $output->writeln(\sprintf(' - %s', $name));
        }
    }

    /**
     * @param string $poolArg
     * @param bool $all
     *
     * @return array<int, string>
     */
    private function resolveTargets(string $poolArg, bool $all, OutputInterface $output): array
    {
        $names = $this->cacheNames();
        if ($all) {
            return $names;
        }

        if ($poolArg !== '') {
            if (!\in_array($poolArg, $names, true)) {
                $output->writeln(\sprintf('<error>Cache "%s" is not defined.</error>', $poolArg));
                return [];
            }

            return [$poolArg];
        }

        if (\in_array('config', $names, true)) {
            return ['config'];
        }

        return $names !== [] ? [\reset($names)] : [];
    }

    /**
     * @return array<int, string>
     */
    private function cacheNames(): array
    {
        $names = $this->configuration->cacheNames();

        return $names === [] ? ['config', 'routes', 'translations'] : $names;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeName(string $name): string
    {
        return $name === 'routing' ? 'routes' : $name;
    }
}
