<?php

declare(strict_types=1);

namespace LPWork\Foundation\Contracts;

/**
 * Defines the contract for readable compiled cache.
 */
interface ReadableCompiledCache extends CompiledCache
{
    /**
     * Returns path.
     */
    public function path(): string;
}
