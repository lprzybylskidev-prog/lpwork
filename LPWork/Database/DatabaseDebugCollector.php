<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Contracts\QueryReporter;
use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the database debug collector framework component.
 */
final class DatabaseDebugCollector implements QueryReporter
{
    /**
     * @var list<QueryExecution>
     */
    private array $executions = [];

    /**
     * Creates a new DatabaseDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 50,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(QueryExecution $execution): void
    {
        $this->executions[] = $execution;

        if (count($this->executions) > $this->limit) {
            array_shift($this->executions);
        }

        $this->metrics?->report(new Metric(
            name: 'database.query.duration',
            value: $execution->durationMs,
            unit: 'ms',
            tags: [
                'connection' => $execution->connection,
                'successful' => $execution->successful,
            ],
        ));
    }

    /**
     * @return list<QueryExecution>
     */
    public function recent(): array
    {
        return $this->executions;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->executions = [];
    }
}
