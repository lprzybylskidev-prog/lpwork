<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Container\Container;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Database\Migrations\MigrationResolver;
use LPWork\Database\Migrations\Migrator;

final readonly class MigrationTestHarness
{
    /**
     * @param array<string, list<class-string<Migration>>> $migrations
     */
    public function __construct(
        private MigrationTestEnvironment $environment,
        private array $migrations,
    ) {}

    public function registry(): MigrationRegistry
    {
        $registry = new MigrationRegistry();

        foreach ($this->migrations as $connection => $migrations) {
            $registry->add($connection, $migrations);
        }

        return $registry;
    }

    public function migrator(): Migrator
    {
        return new Migrator(
            $this->environment->database(),
            $this->registry(),
            new MigrationResolver(new Container()),
        );
    }
}
