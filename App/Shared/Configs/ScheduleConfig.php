<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures scheduler locking, run storage, and schedule history retention.
 */
final class ScheduleConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'schedule';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // Lock TTL is measured in seconds and prevents overlapping scheduled task runs.
            'lock_ttl_seconds' => (int) Environment::get('SCHEDULE_LOCK_TTL_SECONDS', '900'),
            'database' => [
                // Scheduler run state is stored in the configured database connection/table.
                'connection' => Environment::get('SCHEDULE_DATABASE_CONNECTION', Environment::get('DB_CONNECTION', 'default')),
                'runs_table' => Environment::get('SCHEDULE_RUNS_TABLE', 'schedule_runs'),
            ],
            // History retention controls cleanup of completed scheduler run records.
            'history' => [
                'enabled' => Environment::get('SCHEDULE_HISTORY_ENABLED', 'true') !== 'false',
                'retention_seconds' => (int) Environment::get('SCHEDULE_HISTORY_RETENTION_SECONDS', '2592000'),
            ],
        ];
    }
}
