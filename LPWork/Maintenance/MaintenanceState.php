<?php

declare(strict_types=1);

namespace LPWork\Maintenance;

/**
 * Represents the maintenance state framework component.
 */
final readonly class MaintenanceState
{
    /**
     * Creates a new MaintenanceState instance.
     */
    public function __construct(
        private bool $active,
        private ?string $retryAfter = null,
    ) {}

    /**
     * Performs the inactive operation.
     */
    public static function inactive(): self
    {
        return new self(false);
    }

    /**
     * Performs the active operation.
     */
    public static function active(?string $retryAfter = null): self
    {
        return new self(true, $retryAfter);
    }

    /**
     * Reports whether is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Performs the retry after operation.
     */
    public function retryAfter(): ?string
    {
        return $this->retryAfter;
    }
}
