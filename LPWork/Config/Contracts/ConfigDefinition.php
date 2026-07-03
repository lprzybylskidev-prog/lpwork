<?php

declare(strict_types=1);

namespace LPWork\Config\Contracts;

/**
 * Defines the contract for config definition.
 */
interface ConfigDefinition
{
    /**
     * Returns the stable key used to identify this object.
     */
    public function key(): string;

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array;
}
