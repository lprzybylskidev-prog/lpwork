<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Database\Seeder\SeederRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs seeders for database connections.
 */
class DatabaseSeedCommand extends Command
{
    /**
     * @var SeederRunner
     */
    private SeederRunner $seederRunner;

    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $connectionManager;

    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $config;

    /**
     * @param SeederRunner               $seederRunner
     * @param DatabaseConnectionManager  $connectionManager
     * @param ConfigRepositoryInterface  $config
     */
    public function __construct(
        SeederRunner $seederRunner,
        DatabaseConnectionManager $connectionManager,
        ConfigRepositoryInterface $config,
    ) {
        $this->seederRunner = $seederRunner;
        $this->connectionManager = $connectionManager;
        $this->config = $config;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:database:seed')
            ->setAliases(['database:seed', 'db:seed'])
            ->setDescription('Run database seeders')
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'Connection name',
                $this->connectionManager->getDefaultConnectionName(),
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Run seeders for all configured connections',
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $env = $this->config->getString('app.env', 'prod');

        if ($env !== 'dev') {
            $output->writeln('<error>database:seed is allowed only in dev environment.</error>');

            return Command::FAILURE;
        }

        if ($input->getOption('all')) {
            foreach ($this->connectionManager->getConnectionNames() as $name) {
                $count = $this->seederRunner->seed($name);

                if ($count === 0) {
                    $output->writeln(
                        \sprintf('<comment>No seeders registered for "%s".</comment>', $name),
                    );
                } else {
                    $output->writeln(
                        \sprintf(
                            '<info>Seeders executed for "%s" (count: %d).</info>',
                            $name,
                            $count,
                        ),
                    );
                }
            }

            return Command::SUCCESS;
        }

        $connection = (string) $input->getArgument('connection');
        $count = $this->seederRunner->seed($connection);

        if ($count === 0) {
            $output->writeln(
                \sprintf('<comment>No seeders registered for "%s".</comment>', $connection),
            );
        } else {
            $output->writeln(
                \sprintf(
                    '<info>Seeders executed for "%s" (count: %d).</info>',
                    $connection,
                    $count,
                ),
            );
        }

        return Command::SUCCESS;
    }
}
