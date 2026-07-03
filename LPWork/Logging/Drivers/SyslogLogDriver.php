<?php

declare(strict_types=1);

namespace LPWork\Logging\Drivers;

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Contracts\LogFormatter;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\Formatters\LineLogFormatter;
use LPWork\Logging\LogRecord;

/**
 * Represents the syslog log driver framework component.
 */
final readonly class SyslogLogDriver implements LogDriver
{
    /**
     * Creates a new SyslogLogDriver instance.
     */
    public function __construct(
        private string $ident = 'lpwork',
        private int $facility = LOG_USER,
        private LogFormatter $formatter = new LineLogFormatter(),
    ) {}

    /**
     * Registers or stores save.
     */
    public function save(LogRecord $record): void
    {
        openlog($this->ident, LOG_PID, $this->facility);
        syslog($this->priority($record->level), $this->formatter->format($record));
        closelog();
    }

    private function priority(LogLevel $level): int
    {
        return match ($level) {
            LogLevel::Debug => LOG_DEBUG,
            LogLevel::Info => LOG_INFO,
            LogLevel::Notice => LOG_NOTICE,
            LogLevel::Warning => LOG_WARNING,
            LogLevel::Error => LOG_ERR,
            LogLevel::Critical => LOG_CRIT,
        };
    }
}
