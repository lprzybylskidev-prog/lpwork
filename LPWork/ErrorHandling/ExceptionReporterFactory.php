<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling;

use LPWork\Config\Config;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Reporters\LoggingExceptionReporter;
use LPWork\Logging\LogManager;

/**
 * Creates exception reporter factory instances from framework configuration.
 */
final readonly class ExceptionReporterFactory
{
    /**
     * Performs the logging operation.
     */
    public static function logging(string $basePath): ExceptionReporter
    {
        $logManager = new LogManager(Config::getArray('logging'), $basePath);

        return new LoggingExceptionReporter($logManager->channel('error'));
    }
}
