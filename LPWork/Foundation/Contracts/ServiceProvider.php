<?php

declare(strict_types=1);

namespace LPWork\Foundation\Contracts;

use LPWork\Container\Container;

/**
 * Defines the contract for service provider.
 */
interface ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void;
}
