<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers schedule debug context provider services with the framework container.
 */
final readonly class ScheduleDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new ScheduleDebugContextProvider instance.
     */
    public function __construct(
        private ScheduleDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Scheduler' => [
                'Tasks' => array_map(static fn(ScheduleDebugRecord $record): array => [
                    'Status' => $record->status,
                    'Task' => $record->task,
                    'Type' => $record->type,
                    'Target' => $record->target,
                    'Duration ms' => $record->durationMs,
                    'Context' => $record->context,
                ], $this->collector->recent()),
            ],
        ];
    }
}
