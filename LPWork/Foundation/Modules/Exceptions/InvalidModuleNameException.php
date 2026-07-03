<?php

declare(strict_types=1);

namespace LPWork\Foundation\Modules\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid module name exception failures.
 */
final class InvalidModuleNameException extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Module name cannot be empty.');
    }

    /**
     * Performs the invalid operation.
     */
    public static function invalid(string $name): self
    {
        return new self("Invalid module name [{$name}]. Use a PHP class name or nested module path.");
    }
}
