<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Database\Migrations\Exceptions\MigrationConnectionNotRegisteredException;
use LPWork\Database\Migrations\MigrationCommandResultRenderer;
use LPWork\Database\Migrations\Migrator;
use LPWork\Database\Seeders\DatabaseSeeder;

/**
 * Handles the migrate fresh command console command.
 */
final readonly class MigrateFreshCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new MigrateFreshCommand instance.
     */
    public function __construct(
        private Migrator $migrator,
        private DatabaseSeeder $seeder,
        private MigrationCommandResultRenderer $results = new MigrationCommandResultRenderer(),
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'migrate:fresh';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Rollback all migrations and run them again.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        try {
            $result = $this->migrator->fresh($this->connection($input), $input->hasOption('all'));
        } catch (MigrationConnectionNotRegisteredException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        $this->results->rolledBack($result->rolledBack, $output);
        $this->results->migrated($result->migrated, $output);

        if ($input->hasOption('seed')) {
            try {
                $seedExecutions = $this->seeder->seed($this->connection($input), $input->hasOption('all'));
            } catch (MigrationConnectionNotRegisteredException $exception) {
                $this->messages->error($output, $exception->getMessage());

                return 1;
            }

            $this->results->seeded($seedExecutions, $output);
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
            ConsoleOption::value('connection', description: 'Database connection to refresh.'),
            ConsoleOption::flag('all', description: 'Refresh migrations for all registered connections.'),
            ConsoleOption::flag('seed', description: 'Run seeders after refreshing migrations.'),
            ConsoleOption::flag('force', description: 'Allow refreshing migrations in production.'),
        ];
    }

    /**
     * Performs the middleware operation.
     */
    public function middleware(): array
    {
        return [
            ProductionSafetyMiddleware::class,
        ];
    }

    /**
     * Performs the production safety message operation.
     */
    public function productionSafetyMessage(): string
    {
        return 'Refusing to refresh migrations in production without --force.';
    }

    private function connection(Input $input): ?string
    {
        $connection = $input->option('connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
}
