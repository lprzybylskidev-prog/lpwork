<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Database\Migration\MigrationRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs migrations for a given connection.
 */
class MigrateCommand extends Command
{
    /**
     * @var MigrationRunner
     */
    private MigrationRunner $runner;

    /**
     * @param MigrationRunner $runner
     */
    public function __construct(MigrationRunner $runner)
    {
        $this->runner = $runner;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:migrate')
            ->setAliases(['migrate'])
            ->setDescription('Run database migrations')
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'Connection name',
                $this->runner->getDefaultConnectionName(),
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Run migrations for all configured connections',
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('all')) {
            $results = $this->runner->migrateAll();

            foreach ($results as $name => $executed) {
                $this->renderExecutedMigrations($output, $name, $executed);
            }

            return Command::SUCCESS;
        }

        $connection = (string) $input->getArgument('connection');
        $executed = $this->runner->migrate($connection);

        $this->renderExecutedMigrations($output, $connection, $executed);

        return Command::SUCCESS;
    }

    /**
     * Renders executed migrations for a connection.
     *
     * @param OutputInterface                        $output
     * @param string                                 $connection
     * @param array<int, array<string, string>> $executed
     *
     * @return void
     */
    private function renderExecutedMigrations(
        OutputInterface $output,
        string $connection,
        array $executed,
    ): void {
        if ($executed === []) {
            $output->writeln(
                \sprintf(
                    '<comment>No pending migrations for connection "%s".</comment>',
                    $connection,
                ),
            );

            return;
        }

        $output->writeln(
            \sprintf('<info>Migrations completed for connection "%s":</info>', $connection),
        );

        foreach ($executed as $migration) {
            $output->writeln(
                \sprintf(
                    '- %s (%s) [%s]',
                    $migration['version'],
                    $migration['description'],
                    $migration['class'],
                ),
            );
        }
    }
}
