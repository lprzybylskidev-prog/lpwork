<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Database\DatabaseConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists configured database connections.
 */
class DatabaseConnectionsCommand extends Command
{
    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $connectionManager;

    /**
     * @param DatabaseConnectionManager $connectionManager
     */
    public function __construct(DatabaseConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:database:connections")
            ->setAliases(["database:connections"])
            ->setDescription("List available database connections");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $connections = $this->connectionManager->getConnectionNames();
        $default = $this->connectionManager->getDefaultConnectionName();

        if ($connections === []) {
            $output->writeln(
                "<comment>No database connections are configured.</comment>",
            );

            return Command::SUCCESS;
        }

        $output->writeln("<info>Configured database connections:</info>");

        foreach ($connections as $name) {
            $label = $name === $default ? " (default)" : "";
            $output->writeln(\sprintf("- %s%s", $name, $label));
        }

        return Command::SUCCESS;
    }
}
