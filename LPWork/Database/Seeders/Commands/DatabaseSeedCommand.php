<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Commands;

use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Database\Migrations\MigrationCommandResultRenderer;
use LPWork\Database\Seeders\DatabaseSeeder;

/**
 * Handles the database seed command console command.
 */
final readonly class DatabaseSeedCommand implements Command, DescribesInput, HasConsoleMiddleware, ProductionSensitiveCommand
{
    /**
     * Creates a new DatabaseSeedCommand instance.
     */
    public function __construct(
        private DatabaseSeeder $seeder,
        private MigrationCommandResultRenderer $results = new MigrationCommandResultRenderer(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'db:seed';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Run registered database seeders.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $executions = $this->seeder->seed($this->connection($input), $input->hasOption('all'));
        $this->results->seeded($executions, $output);

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
            ConsoleOption::value('connection', description: 'Database connection to seed.'),
            ConsoleOption::flag('all', description: 'Run seeders for all registered connections.'),
            ConsoleOption::flag('force', description: 'Allow seeding in production.'),
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
        return 'Refusing to seed databases in production without --force.';
    }

    private function connection(Input $input): ?string
    {
        $connection = $input->option('connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
}
