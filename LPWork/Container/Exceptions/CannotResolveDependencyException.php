<?php

declare(strict_types=1);

namespace LPWork\Container\Exceptions;

use RuntimeException;

/**
 * Reports cannot resolve dependency exception failures.
 */
final class CannotResolveDependencyException extends RuntimeException
{
    /**
     * Performs the class does not exist operation.
     */
    public static function classDoesNotExist(string $id): self
    {
        return new self("Cannot resolve container entry [{$id}]: class does not exist.");
    }

    /**
     * Performs the class is not instantiable operation.
     */
    public static function classIsNotInstantiable(string $id): self
    {
        return new self("Cannot resolve container entry [{$id}]: class is not instantiable.");
    }

    /**
     * Performs the parameter has no type operation.
     */
    public static function parameterHasNoType(string $class, string $parameter): self
    {
        return new self("Cannot resolve [{$class}]: constructor parameter [\${$parameter}] has no type.");
    }

    /**
     * Performs the parameter has builtin type operation.
     */
    public static function parameterHasBuiltinType(string $class, string $parameter, string $type): self
    {
        return new self("Cannot resolve [{$class}]: constructor parameter [\${$parameter}] uses builtin type [{$type}]. Register it with a factory binding.");
    }

    /**
     * Performs the parameter has unsupported type operation.
     */
    public static function parameterHasUnsupportedType(string $class, string $parameter): self
    {
        return new self("Cannot resolve [{$class}]: constructor parameter [\${$parameter}] uses unsupported union or intersection type. Register it with a factory binding.");
    }

    /**
     * Performs the parameter binding missing operation.
     */
    public static function parameterBindingMissing(string $class, string $parameter, string $dependency): self
    {
        return new self("Cannot resolve [{$class}]: constructor parameter [\${$parameter}] expects [{$dependency}], but no binding exists.");
    }

    /**
     * Performs the factory did not return object operation.
     */
    public static function factoryDidNotReturnObject(string $id): self
    {
        return new self("Cannot resolve container entry [{$id}]: factory must return an object.");
    }
}
