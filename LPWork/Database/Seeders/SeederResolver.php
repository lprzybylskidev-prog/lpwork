<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders;

use LPWork\Container\Container;
use LPWork\Database\Seeders\Contracts\Seeder;
use LPWork\Database\Seeders\Exceptions\InvalidSeederException;

/**
 * Resolves seeder resolver values into runtime objects.
 */
final readonly class SeederResolver
{
    /**
     * Creates a new SeederResolver instance.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param class-string<Seeder> $seeder
     */
    public function resolve(string $seeder): Seeder
    {
        $instance = $this->container->make($seeder);

        if (!$instance instanceof Seeder) {
            throw new InvalidSeederException($seeder);
        }

        return $instance;
    }
}
