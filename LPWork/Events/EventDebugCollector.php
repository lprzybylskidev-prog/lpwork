<?php

declare(strict_types=1);

namespace LPWork\Events;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the event debug collector framework component.
 */
final class EventDebugCollector
{
    /**
     * @var list<EventDispatchRecord>
     */
    private array $records = [];

    /**
     * Creates a new EventDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 50,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * Performs the start operation.
     */
    public function start(object $event): EventDispatchRecord
    {
        $record = new EventDispatchRecord($event::class);
        $this->records[] = $record;

        if (count($this->records) > $this->limit) {
            array_shift($this->records);
        }

        return $record;
    }

    /**
     * Performs the finish operation.
     */
    public function finish(EventDispatchRecord $record, float $durationMs, bool $successful, float $recordedAtMs): void
    {
        $record->finish($durationMs, $successful);

        $this->metrics?->report(new Metric(
            name: 'events.dispatched',
            value: $durationMs,
            unit: 'ms',
            tags: [
                'event' => $record->event,
                'listeners' => count($record->listeners),
                'successful' => $successful,
            ],
            recordedAtMs: $recordedAtMs,
        ));
    }

    /**
     * @return list<EventDispatchRecord>
     */
    public function recent(): array
    {
        return $this->records;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->records = [];
    }
}
