<?php

declare(strict_types=1);

namespace LPWork\Cache;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the cache debug collector framework component.
 */
final class CacheDebugCollector
{
    /**
     * @var list<CacheDebugRecord>
     */
    private array $records = [];

    /**
     * Creates a new CacheDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 100,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function record(string $store, string $operation, string $key, float $durationMs, bool $successful, array $context = []): void
    {
        $record = new CacheDebugRecord($store, $operation, $key, $durationMs, $successful, $context);
        $this->records[] = $record;

        if (count($this->records) > $this->limit) {
            array_shift($this->records);
        }

        $this->metrics?->report(new Metric(
            name: 'cache.operation.duration',
            value: $durationMs,
            unit: 'ms',
            tags: [
                'store' => $store,
                'operation' => $operation,
                'successful' => $successful,
            ],
        ));
    }

    /**
     * @return list<CacheDebugRecord>
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
