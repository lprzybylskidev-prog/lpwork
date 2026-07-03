<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;

/**
 * Resolves typed console provider dependencies from the application container.
 */
final readonly class ConsoleContainerResolver
{
    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public static function require(Container $container, string $id): object
    {
        $resolved = $container->make($id);

        if (!$resolved instanceof $id) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject($id);
        }

        return $resolved;
    }
}
