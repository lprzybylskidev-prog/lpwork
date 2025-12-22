<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\Migration\MigrationRunner;
use LPwork\Database\Seeder\SeederRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drops schema and reruns migrations, optional seeding.
 */
class MigrateFreshCommand extends Command
{
    /**
     * @var MigrationRunner
     */
    private MigrationRunner $runner;

    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $config;

    /**
     * @var SeederRunner
     */
    private SeederRunner $seederRunner;

    /**
     * @param MigrationRunner            $runner
     * @param ConfigRepositoryInterface  $config
     * @param SeederRunner               $seederRunner
     */
    public function __construct(
        MigrationRunner $runner,
        ConfigRepositoryInterface $config,
        SeederRunner $seederRunner,
    ) {
        $this->runner = $runner;
        $this->config = $config;
        $this->seederRunner = $seederRunner;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:migrate:fresh")
            ->setAliases(["migrate:fresh"])
            ->setDescription("Drop all tables and rerun migrations")
            ->addArgument(
                "connection",
                InputArgument::OPTIONAL,
                "Connection name",
                $this->runner->getDefaultConnectionName(),
            )
            ->addOption(
                "seed",
                null,
                InputOption::VALUE_NONE,
                "Run seeders after migration",
            )
            ->addOption(
                "all",
                null,
                InputOption::VALUE_NONE,
                "Run fresh migration for all configured connections",
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $env = $this->config->getString("app.env", "prod");

        if ($env !== "dev") {
            $output->writeln(
                "<error>migrate:fresh is allowed only in dev environment.</error>",
            );

            return Command::FAILURE;
        }

        $connection = (string) $input->getArgument("connection");

        if ($input->getOption("all")) {
            $results = $this->runner->freshAll();

            foreach ($results as $name => $executed) {
                $this->renderExecutedMigrations($output, $name, $executed);

                if ($input->getOption("seed")) {
                    $count = $this->seederRunner->seed($name);

                    if ($count === 0) {
                        $output->writeln(
                            \sprintf(
                                '<comment>No seeders registered for "%s".</comment>',
                                $name,
                            ),
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
            }

            return Command::SUCCESS;
        }

        $executed = $this->runner->fresh($connection);
        $this->renderExecutedMigrations($output, $connection, $executed);

        if ($input->getOption("seed")) {
            $count = $this->seederRunner->seed($connection);

            if ($count === 0) {
                $output->writeln(
                    \sprintf(
                        '<comment>No seeders registered for "%s".</comment>',
                        $connection,
                    ),
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
        }

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
            \sprintf(
                '<info>Fresh migration completed for connection "%s":</info>',
                $connection,
            ),
        );

        foreach ($executed as $migration) {
            $output->writeln(
                \sprintf(
                    "- %s (%s) [%s]",
                    $migration["version"],
                    $migration["description"],
                    $migration["class"],
                ),
            );
        }
    }
}
