<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the throttle debug collector framework component.
 */
final class ThrottleDebugCollector
{
    /**
     * @var list<ThrottleDebugRecord>
     */
    private array $records = [];

    /**
     * Creates a new ThrottleDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 100,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function throttled(string $flow, array $context): void
    {
        $this->records[] = new ThrottleDebugRecord($flow, $context);

        if (count($this->records) > $this->limit) {
            array_shift($this->records);
        }

        $this->metrics?->report(new Metric(
            name: 'throttle.denied',
            value: 1,
            unit: 'count',
            tags: [
                'flow' => $flow,
            ],
        ));
    }

    /**
     * @return list<ThrottleDebugRecord>
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
