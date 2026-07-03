<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Container\Container;

/**
 * Defines the contract for console service registrar.
 */
interface ConsoleServiceRegistrar
{
    /**
     * Adds one console service area to the application container.
     */
    public function register(Container $container): void;
}
