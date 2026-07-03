<?php

declare(strict_types=1);

namespace LPWork\Health;

/**
 * Represents the health report framework component.
 */
final readonly class HealthReport
{
    /**
     * @param list<HealthCheckResult> $checks
     */
    public function __construct(
        private array $checks,
    ) {}

    /**
     * Reports whether is healthy.
     */
    public function isHealthy(): bool
    {
        foreach ($this->checks as $check) {
            if (!$check->isHealthy()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the current status value.
     */
    public function status(): string
    {
        return $this->isHealthy() ? 'ok' : 'failed';
    }

    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return $this->isHealthy() ? 200 : 503;
    }

    /**
     * Returns exit code.
     */
    public function exitCode(): int
    {
        return $this->isHealthy() ? 0 : 1;
    }

    /**
     * @return list<HealthCheckResult>
     */
    public function checks(): array
    {
        return $this->checks;
    }

    /**
     * @return array{status: string, checks: list<array{name: string, status: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status(),
            'checks' => array_map(
                static fn(HealthCheckResult $check): array => $check->toArray(),
                $this->checks,
            ),
        ];
    }
}
