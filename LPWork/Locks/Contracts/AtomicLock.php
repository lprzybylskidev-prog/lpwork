<?php

declare(strict_types=1);

namespace LPWork\Locks\Contracts;

/**
 * Defines the contract for atomic lock.
 */
interface AtomicLock
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string;

    /**
     * Performs the owner operation.
     */
    public function owner(): string;

    /**
     * Performs the acquire operation.
     */
    public function acquire(): bool;

    /**
     * Removes or clears release.
     */
    public function release(): bool;
}
