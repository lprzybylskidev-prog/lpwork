<?php

declare(strict_types=1);

namespace LPWork\Logging;

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Represents the log channel framework component.
 */
final class LogChannel implements Logger
{
    /**
     * Creates a new LogChannel instance.
     */
    public function __construct(
        private readonly string $name,
        private readonly LogDriver $driver,
        private readonly LogLevel $minimumLevel = LogLevel::Debug,
        private readonly LogMessageInterpolator $interpolator = new LogMessageInterpolator(),
        private readonly Clock $clock = new SystemClock(),
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function log(LogLevel $level, string $message, array $context = []): void
    {
        if (!$level->allows($this->minimumLevel)) {
            return;
        }

        $this->driver->save(
            new LogRecord(
                channel: $this->name,
                level: $level,
                message: $this->interpolator->interpolate($message, $context),
                context: $context,
                datetime: $this->clock->now(),
            ),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::Debug, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::Info, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::Notice, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::Warning, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::Error, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::Critical, $message, $context);
    }
}
