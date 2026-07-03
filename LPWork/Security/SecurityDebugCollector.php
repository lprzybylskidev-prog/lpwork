<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the security debug collector framework component.
 */
final class SecurityDebugCollector
{
    /**
     * @var list<SecurityDebugRecord>
     */
    private array $records = [];

    /**
     * Creates a new SecurityDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 100,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function denied(string $reason, string $message, array $context): void
    {
        $this->records[] = new SecurityDebugRecord($reason, $message, $context);

        if (count($this->records) > $this->limit) {
            array_shift($this->records);
        }

        $this->metrics?->report(new Metric(
            name: 'security.http.denied',
            value: 1,
            unit: 'count',
            tags: [
                'reason' => $reason,
            ],
        ));
    }

    /**
     * @return list<SecurityDebugRecord>
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
