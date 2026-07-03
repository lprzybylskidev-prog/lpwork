<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid routing config exception failures.
 */
final class InvalidRoutingConfigException extends InvalidArgumentException
{
    /**
     * Performs the middleware aliases must be map operation.
     */
    public static function middlewareAliasesMustBeMap(): self
    {
        return new self('Routing middleware aliases must be a map of alias names to middleware class names.');
    }

    /**
     * Performs the middleware groups must be map operation.
     */
    public static function middlewareGroupsMustBeMap(): self
    {
        return new self('Routing middleware groups must be a map of group names to middleware lists.');
    }

    /**
     * Performs the global middleware must be list operation.
     */
    public static function globalMiddlewareMustBeList(): self
    {
        return new self('Global route middleware must be a list of middleware aliases or class names.');
    }

    /**
     * Performs the middleware group must contain strings operation.
     */
    public static function middlewareGroupMustContainStrings(string $group): self
    {
        return new self(sprintf(
            'Routing middleware group [%s] must contain only middleware aliases or class names.',
            $group,
        ));
    }

    /**
     * Performs the middleware alias name is invalid operation.
     */
    public static function middlewareAliasNameIsInvalid(string $alias): self
    {
        return new self(sprintf('Routing middleware alias name is invalid: %s.', $alias));
    }

    /**
     * Performs the middleware group name is invalid operation.
     */
    public static function middlewareGroupNameIsInvalid(string $group): self
    {
        return new self(sprintf('Routing middleware group name is invalid: %s.', $group));
    }

    /**
     * Performs the route group name is invalid operation.
     */
    public static function routeGroupNameIsInvalid(string $name): self
    {
        return new self(sprintf('Route group name is invalid: %s.', $name));
    }

    /**
     * Performs the middleware class does not exist operation.
     */
    public static function middlewareClassDoesNotExist(string $middleware): self
    {
        return new self(sprintf('Route middleware class or alias is not registered: %s.', $middleware));
    }

    /**
     * Performs the middleware class is invalid operation.
     */
    public static function middlewareClassIsInvalid(string $middleware): self
    {
        return new self(sprintf('Route middleware must implement the middleware contract: %s.', $middleware));
    }
}
