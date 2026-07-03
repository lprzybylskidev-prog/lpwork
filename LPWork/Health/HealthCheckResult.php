<?php

declare(strict_types=1);

namespace LPWork\Health;

/**
 * Represents the result of health check result work.
 */
final readonly class HealthCheckResult
{
    private function __construct(
        private string $name,
        private bool $healthy,
        private string $message,
    ) {}

    /**
     * Performs the healthy operation.
     */
    public static function healthy(string $name, string $message = 'OK'): self
    {
        return new self($name, true, $message);
    }

    /**
     * Performs the unhealthy operation.
     */
    public static function unhealthy(string $name, string $message): self
    {
        return new self($name, false, $message);
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Reports whether is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->healthy;
    }

    /**
     * Returns the current status value.
     */
    public function status(): string
    {
        return $this->healthy ? 'ok' : 'failed';
    }

    /**
     * Performs the message operation.
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status(),
            'message' => $this->message,
        ];
    }
}
