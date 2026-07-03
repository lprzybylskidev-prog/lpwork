<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

use LPWork\Container\Container;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Migrations\Exceptions\InvalidMigrationException;

/**
 * Resolves migration resolver values into runtime objects.
 */
final readonly class MigrationResolver
{
    /**
     * Creates a new MigrationResolver instance.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param class-string<Migration> $migration
     */
    public function resolve(string $migration): Migration
    {
        $instance = $this->container->make($migration);

        if (!$instance instanceof Migration) {
            throw new InvalidMigrationException($migration);
        }

        return $instance;
    }
}
