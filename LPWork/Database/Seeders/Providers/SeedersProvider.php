<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Providers;

use LPWork\Container\Container;
use LPWork\Database\Seeders\Contracts\Seeder;
use LPWork\Database\Seeders\SeederRegistry;

/**
 * Registers seeders provider services with the framework container.
 */
abstract class SeedersProvider extends \LPWork\Foundation\ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $registry = $container->make(SeederRegistry::class);

        if (!$registry instanceof SeederRegistry) {
            return;
        }

        foreach ($this->seeders() as $connection => $seeders) {
            $registry->add($connection, $seeders);
        }
    }

    /**
     * @return array<string, list<class-string<Seeder>>>
     */
    abstract protected function seeders(): array;
}
