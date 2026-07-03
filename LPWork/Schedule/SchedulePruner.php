<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Represents the schedule pruner framework component.
 */
final readonly class SchedulePruner
{
    /**
     * Creates a new SchedulePruner instance.
     */
    public function __construct(
        private ScheduleStoreFactory $stores,
        private int $historyRetentionSeconds,
    ) {}

    /**
     * @return array{locks: int, runs: int}
     */
    public function prune(): array
    {
        $store = $this->stores->create();

        return [
            'locks' => 0,
            'runs' => $store->pruneRunHistory($this->historyRetentionSeconds),
        ];
    }
}
