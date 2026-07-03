<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Logging\Enums\LogLevel;

/**
 * Represents the diagnostics collector framework component.
 */
final class DiagnosticsCollector
{
    /**
     * @var list<array{channel: string, level: string, message: string, context: array<string, mixed>}>
     */
    private array $logs = [];

    /**
     * Creates a new DiagnosticsCollector instance.
     */
    public function __construct(
        private readonly SensitiveValueRedactor $redactor = new SensitiveValueRedactor(),
        private readonly int $limit = 100,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function recordLog(string $channel, LogLevel $level, string $message, array $context = []): void
    {
        $this->logs[] = [
            'channel' => $channel,
            'level' => $level->value,
            'message' => $message,
            'context' => $this->redactor->redact($context),
        ];

        if (count($this->logs) > $this->limit) {
            array_shift($this->logs);
        }
    }

    /**
     * @return list<array{channel: string, level: string, message: string, context: array<string, mixed>}>
     */
    public function logs(): array
    {
        return $this->logs;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->logs = [];
    }
}
