<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

use LPWork\Console\ConsoleTable;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;
use LPWork\Console\Output;

/**
 * Renders migration status renderer output.
 */
final readonly class MigrationStatusRenderer
{
    /**
     * Creates a new MigrationStatusRenderer instance.
     */
    public function __construct(
        private ConsoleTableRenderer $tables,
    ) {}

    /**
     * @param list<MigrationStatus> $statuses
     */
    public function render(array $statuses, Output $output): void
    {
        if ($statuses === []) {
            $output->writelnFormatted('No migrations registered.', ConsoleColor::Gray);

            return;
        }

        $output->writelnFormatted('Migration status:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(
            ['Connection', 'Ran', 'Batch', 'Migration'],
            array_map(
                static fn(MigrationStatus $status): array => [
                    $status->connection,
                    $status->ran ? 'yes' : 'no',
                    $status->batch === null ? '-' : (string) $status->batch,
                    $status->migration,
                ],
                $statuses,
            ),
        ), $output);
    }
}
