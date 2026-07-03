<?php

declare(strict_types=1);

namespace LPWork\Foundation\Exceptions;

use LogicException;

/**
 * Reports duplicate compiled cache exception failures.
 */
final class DuplicateCompiledCacheException extends LogicException
{
    /**
     * Performs the for name operation.
     */
    public static function forName(string $name): self
    {
        return new self("Compiled cache [{$name}] is already registered.");
    }
}
