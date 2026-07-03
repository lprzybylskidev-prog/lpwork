<?php

declare(strict_types=1);

namespace LPWork\Database\Contracts;

use LPWork\Database\QueryExecution;

/**
 * Defines the contract for query reporter.
 */
interface QueryReporter
{
    /**
     * Performs the report operation.
     */
    public function report(QueryExecution $execution): void;
}
