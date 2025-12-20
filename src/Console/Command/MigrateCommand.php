<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Database\Migration\MigrationRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        parent::__construct();
        $this->runner = $runner;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:migrate:run")
            ->setAliases(["migrate", "lpwork:migrate"])
            ->setDescription("Run database migrations")
            ->addArgument(
                "connection",
                InputArgument::OPTIONAL,
                "Connection name",
                "default",
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $connection = (string) $input->getArgument("connection");

        $this->runner->migrate($connection);

        $output->writeln(
            \sprintf(
                '<info>Migrations completed for connection "%s".</info>',
                $connection,
            ),
        );

        return Command::SUCCESS;
    }
}
