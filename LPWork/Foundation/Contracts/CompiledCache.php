<?php

declare(strict_types=1);

namespace LPWork\Foundation\Contracts;

/**
 * Defines the contract for compiled cache.
 */
interface CompiledCache
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string;

    /**
     * Returns label.
     */
    public function label(): string;

    /**
     * @return list<string>
     */
    public function aliases(): array;

    /**
     * Reports whether exists.
     */
    public function exists(): bool;

    /**
     * Performs the rebuild operation.
     */
    public function rebuild(): void;
}
