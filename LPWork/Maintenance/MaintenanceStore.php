<?php

declare(strict_types=1);

namespace LPWork\Maintenance;

/**
 * Defines the contract for maintenance store.
 */
interface MaintenanceStore
{
    /**
     * Builds or returns read.
     */
    public function read(): MaintenanceState;

    /**
     * Registers or stores write.
     */
    public function write(MaintenanceState $state): void;

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void;
}
