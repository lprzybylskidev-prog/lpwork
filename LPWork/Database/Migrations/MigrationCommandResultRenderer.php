<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Output;
use LPWork\Database\Seeders\SeederExecution;

/**
 * Renders migration command result renderer output.
 */
final readonly class MigrationCommandResultRenderer
{
    /**
     * Creates a new MigrationCommandResultRenderer instance.
     */
    public function __construct(
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * @param list<MigrationExecution> $executions
     */
    public function migrated(array $executions, Output $output): void
    {
        $this->migrationExecutions('Migrated', 'Nothing to migrate.', $executions, $output);
    }

    /**
     * @param list<MigrationExecution> $executions
     */
    public function rolledBack(array $executions, Output $output): void
    {
        $this->migrationExecutions('Rolled back', 'Nothing to rollback.', $executions, $output);
    }

    /**
     * @param list<SeederExecution> $executions
     */
    public function seeded(array $executions, Output $output): void
    {
        if ($executions === []) {
            $this->messages->muted($output, 'Nothing to seed.');

            return;
        }

        $this->messages->success($output, 'Seeded database records.');
        $this->messages->table(
            $output,
            ['Connection', 'Seeder'],
            array_map(
                static fn(SeederExecution $execution): array => [
                    $execution->connection,
                    $execution->seeder,
                ],
                $executions,
            ),
        );
    }

    /**
     * @param list<MigrationExecution> $executions
     */
    private function migrationExecutions(string $action, string $emptyMessage, array $executions, Output $output): void
    {
        if ($executions === []) {
            $this->messages->muted($output, $emptyMessage);

            return;
        }

        $this->messages->success($output, $action . ' migrations.');
        $this->messages->table(
            $output,
            ['Connection', 'Batch', 'Migration'],
            array_map(
                static fn(MigrationExecution $execution): array => [
                    $execution->connection,
                    (string) $execution->batch,
                    $execution->migration,
                ],
                $executions,
            ),
        );
    }
}
