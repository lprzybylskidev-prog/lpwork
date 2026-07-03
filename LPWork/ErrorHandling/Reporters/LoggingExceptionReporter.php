<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Reporters;

use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\Logging\Contracts\Logger;
use Throwable;

/**
 * Represents the logging exception reporter framework component.
 */
final class LoggingExceptionReporter implements ExceptionReporter
{
    /**
     * Creates a new LoggingExceptionReporter instance.
     */
    public function __construct(
        private readonly Logger $logger,
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(Throwable $throwable): void
    {
        $this->logger->error($throwable->getMessage(), [
            'exception' => $throwable::class,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ]);
    }
}
