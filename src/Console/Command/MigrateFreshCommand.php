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
        parent::__construct();
        $this->runner = $runner;
        $this->config = $config;
        $this->seederRunner = $seederRunner;
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
                "default",
            )
            ->addOption(
                "seed",
                null,
                InputOption::VALUE_NONE,
                "Run seeders after migration",
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

        $this->runner->fresh($connection);
        $output->writeln(
            \sprintf(
                '<info>Fresh migration completed for connection "%s".</info>',
                $connection,
            ),
        );

        if ($input->getOption("seed")) {
            $this->seederRunner->seed($connection);
            $output->writeln("<info>Seeders executed.</info>");
        }

        return Command::SUCCESS;
    }
}
