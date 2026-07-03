<?php

declare(strict_types=1);

namespace LPWork\Config;

/**
 * Represents the environment validation report framework component.
 */
final readonly class EnvironmentValidationReport
{
    /**
     * @param list<EnvironmentValidationIssue> $issues
     */
    public function __construct(
        public int $checked,
        private array $issues,
    ) {}

    /**
     * Reports whether is valid.
     */
    public function isValid(): bool
    {
        return $this->issues === [];
    }

    /**
     * Returns the current status value.
     */
    public function status(): string
    {
        return $this->isValid() ? 'valid' : 'invalid';
    }

    /**
     * Returns exit code.
     */
    public function exitCode(): int
    {
        return $this->isValid() ? 0 : 1;
    }

    /**
     * @return list<EnvironmentValidationIssue>
     */
    public function issues(): array
    {
        return $this->issues;
    }
}
