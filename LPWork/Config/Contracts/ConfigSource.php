<?php

declare(strict_types=1);

namespace LPWork\Config\Contracts;

/**
 * Defines the contract for config source.
 */
interface ConfigSource
{
    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function load(): array;
}
