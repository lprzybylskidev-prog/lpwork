<?php

declare(strict_types=1);

namespace LPWork\View\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid php view extension exception failures.
 */
final class InvalidPhpViewExtensionException extends InvalidArgumentException
{
    /**
     * Performs the invalid name operation.
     */
    public static function invalidName(string $name): self
    {
        return new self(sprintf('PHP view extension name must be a valid variable name: %s', $name));
    }

    /**
     * Performs the reserved name operation.
     */
    public static function reservedName(string $name): self
    {
        return new self(sprintf('PHP view extension name is reserved by the view engine: %s', $name));
    }
}
