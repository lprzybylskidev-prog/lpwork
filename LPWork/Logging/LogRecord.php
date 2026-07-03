<?php

declare(strict_types=1);

namespace LPWork\Logging;

use DateTimeImmutable;
use LPWork\Logging\Enums\LogLevel;

/**
 * Represents the log record framework component.
 */
final readonly class LogRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $channel,
        public LogLevel $level,
        public string $message,
        public array $context,
        public DateTimeImmutable $datetime,
    ) {}
}
