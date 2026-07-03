<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Contracts\QueryReporter;

/**
 * Represents the null query reporter framework component.
 */
final readonly class NullQueryReporter implements QueryReporter
{
    /**
     * Performs the report operation.
     */
    public function report(QueryExecution $execution): void {}
}
