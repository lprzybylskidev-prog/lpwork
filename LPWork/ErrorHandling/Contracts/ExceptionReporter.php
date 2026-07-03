<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Contracts;

use Throwable;

/**
 * Defines the contract for exception reporter.
 */
interface ExceptionReporter
{
    /**
     * Performs the report operation.
     */
    public function report(Throwable $throwable): void;
}
