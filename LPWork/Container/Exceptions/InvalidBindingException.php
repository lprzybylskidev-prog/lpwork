<?php

declare(strict_types=1);

namespace LPWork\Container\Exceptions;

use RuntimeException;

/**
 * Reports invalid binding exception failures.
 */
final class InvalidBindingException extends RuntimeException
{
    /**
     * Performs the concrete class does not exist operation.
     */
    public static function concreteClassDoesNotExist(string $abstract, string $concrete): self
    {
        return new self("Cannot bind [{$abstract}] to [{$concrete}]: concrete class does not exist.");
    }

    /**
     * Performs the concrete is not instantiable operation.
     */
    public static function concreteIsNotInstantiable(string $abstract, string $concrete): self
    {
        return new self("Cannot bind [{$abstract}] to [{$concrete}]: concrete class is not instantiable.");
    }
}
