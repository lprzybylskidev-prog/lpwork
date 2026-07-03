<?php

declare(strict_types=1);

namespace LPWork\Maintenance;

/**
 * Represents the maintenance mode framework component.
 */
final readonly class MaintenanceMode
{
    /**
     * Creates a new MaintenanceMode instance.
     */
    public function __construct(private MaintenanceStore $store) {}

    /**
     * Performs the activate operation.
     */
    public function activate(?string $retryAfter = null): MaintenanceState
    {
        $state = MaintenanceState::active($retryAfter);
        $this->store->write($state);

        return $state;
    }

    /**
     * Performs the deactivate operation.
     */
    public function deactivate(): void
    {
        $this->store->clear();
    }

    /**
     * Performs the state operation.
     */
    public function state(): MaintenanceState
    {
        return $this->store->read();
    }
}
