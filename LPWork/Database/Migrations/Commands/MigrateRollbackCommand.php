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

/**
 * Handles the migrate rollback command console command.
 */
final readonly class MigrateRollbackCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new MigrateRollbackCommand instance.
     */
    public function __construct(
        private Migrator $migrator,
        private MigrationCommandResultRenderer $results = new MigrationCommandResultRenderer(),
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'migrate:rollback';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Rollback the latest migration batch.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        try {
            $executions = $this->migrator->rollback($this->connection($input), $input->hasOption('all'));
        } catch (MigrationConnectionNotRegisteredException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        $this->results->rolledBack($executions, $output);

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
            ConsoleOption::value('connection', description: 'Database connection to rollback.'),
            ConsoleOption::flag('all', description: 'Rollback the latest batch for all registered connections.'),
            ConsoleOption::flag('force', description: 'Allow rollback in production.'),
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
        return 'Refusing to rollback migrations in production without --force.';
    }

    private function connection(Input $input): ?string
    {
        $connection = $input->option('connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
}
