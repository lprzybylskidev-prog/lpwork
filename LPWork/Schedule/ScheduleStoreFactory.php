<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Database\DatabaseManager;
use LPWork\Time\Contracts\Clock;

/**
 * Creates schedule store factory instances from framework configuration.
 */
final readonly class ScheduleStoreFactory
{
    /**
     * Creates a new ScheduleStoreFactory instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private Clock $clock,
        private string $connection,
        private string $runsTable,
        private bool $historyEnabled,
    ) {}

    /**
     * Creates a new value for this component.
     */
    public function create(): ScheduleStore
    {
        return new ScheduleStore(
            connection: $this->database->connection($this->connection),
            clock: $this->clock,
            runsTable: $this->runsTable,
            historyEnabled: $this->historyEnabled,
        );
    }
}
