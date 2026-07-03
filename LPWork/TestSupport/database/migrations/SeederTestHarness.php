<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Container\Container;
use LPWork\Database\Seeders\Contracts\Seeder;
use LPWork\Database\Seeders\DatabaseSeeder;
use LPWork\Database\Seeders\SeederRegistry;
use LPWork\Database\Seeders\SeederResolver;

final readonly class SeederTestHarness
{
    /**
     * @param array<string, list<class-string<Seeder>>> $seeders
     */
    public function __construct(
        private MigrationTestEnvironment $environment,
        private array $seeders,
    ) {}

    public function registry(): SeederRegistry
    {
        $registry = new SeederRegistry();

        foreach ($this->seeders as $connection => $seeders) {
            $registry->add($connection, $seeders);
        }

        return $registry;
    }

    public function seeder(): DatabaseSeeder
    {
        return new DatabaseSeeder(
            $this->environment->database(),
            $this->registry(),
            new SeederResolver(new Container()),
        );
    }
}
