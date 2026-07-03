<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;

/**
 * Represents the diagnostics logger framework component.
 */
final readonly class DiagnosticsLogger implements Logger
{
    /**
     * Creates a new DiagnosticsLogger instance.
     */
    public function __construct(
        private Logger $logger,
        private DiagnosticsCollector $collector,
        private string $channel = 'app',
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function log(LogLevel $level, string $message, array $context = []): void
    {
        $this->collector->recordLog($this->channel, $level, $message, $context);
        $this->logger->log($level, $message, $context);
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
