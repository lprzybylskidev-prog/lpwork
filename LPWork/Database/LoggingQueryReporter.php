<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Contracts\QueryReporter;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;

/**
 * Represents the logging query reporter framework component.
 */
final readonly class LoggingQueryReporter implements QueryReporter
{
    /**
     * Creates a new LoggingQueryReporter instance.
     */
    public function __construct(
        private Logger $logger,
        private LogLevel $level,
        private bool $appDebug,
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(QueryExecution $execution): void
    {
        $context = [
            'connection' => $execution->connection,
            'sql' => $execution->sql,
            'duration_ms' => $execution->durationMs,
            'successful' => $execution->successful,
        ];

        if ($this->appDebug) {
            $context['bindings'] = $execution->bindings;
        } else {
            $context['bindings_count'] = count($execution->bindings);
        }

        if ($execution->exception !== null) {
            $context['exception'] = $execution->exception::class;
            $this->logger->error('Database query failed.', $context);

            return;
        }

        $this->logger->log($this->level, 'Database query executed.', $context);
    }
}
