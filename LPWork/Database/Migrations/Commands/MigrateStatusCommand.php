<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Database\Migrations\Exceptions\MigrationConnectionNotRegisteredException;
use LPWork\Database\Migrations\MigrationStatusRenderer;
use LPWork\Database\Migrations\Migrator;

/**
 * Handles the migrate status command console command.
 */
final readonly class MigrateStatusCommand implements Command, DescribesInput
{
    /**
     * Creates a new MigrateStatusCommand instance.
     */
    public function __construct(
        private Migrator $migrator,
        private MigrationStatusRenderer $renderer,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'migrate:status';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Show database migration status.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        try {
            $this->renderer->render(
                $this->migrator->status($this->connection($input), $input->hasOption('all')),
                $output,
            );
        } catch (MigrationConnectionNotRegisteredException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Performs the arguments operation.
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * Returns options.
     */
    public function options(): array
    {
        return [
            ConsoleOption::value('connection', description: 'Database connection to inspect.'),
            ConsoleOption::flag('all', description: 'Show status for all registered connections.'),
        ];
    }

    private function connection(Input $input): ?string
    {
        $connection = $input->option('connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
}
