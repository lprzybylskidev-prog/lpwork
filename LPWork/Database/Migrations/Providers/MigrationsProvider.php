<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Providers;

use LPWork\Container\Container;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Migrations\MigrationRegistry;

/**
 * Registers migrations provider services with the framework container.
 */
abstract class MigrationsProvider extends \LPWork\Foundation\ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $registry = $container->make(MigrationRegistry::class);

        if (!$registry instanceof MigrationRegistry) {
            return;
        }

        foreach ($this->migrations() as $connection => $migrations) {
            $registry->add($connection, $migrations);
        }
    }

    /**
     * @return array<string, list<class-string<Migration>>>
     */
    abstract protected function migrations(): array;
}
