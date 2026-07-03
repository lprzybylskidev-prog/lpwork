<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Contracts\QueryReporter;

/**
 * Represents the composite query reporter framework component.
 */
final readonly class CompositeQueryReporter implements QueryReporter
{
    /**
     * @param list<QueryReporter> $reporters
     */
    public function __construct(
        private array $reporters,
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(QueryExecution $execution): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->report($execution);
        }
    }
}
