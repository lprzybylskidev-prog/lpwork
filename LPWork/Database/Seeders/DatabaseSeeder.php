<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;
use LPWork\Database\Seeders\Exceptions\SeederConnectionNotRegisteredException;

/**
 * Represents the database seeder framework component.
 */
final readonly class DatabaseSeeder
{
    /**
     * Creates a new DatabaseSeeder instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private SeederRegistry $registry,
        private SeederResolver $resolver,
    ) {}

    /**
     * @return list<SeederExecution>
     */
    public function seed(?string $connection = null, bool $all = false): array
    {
        $executions = [];

        foreach ($this->targetConnections($connection, $all) as $target) {
            $db = $this->databaseConnection($target);

            foreach ($this->registry->forConnection($target) as $seederClass) {
                $seeder = $this->resolver->resolve($seederClass);
                $seeder->run($db);
                $executions[] = new SeederExecution($target, $seederClass);
            }
        }

        return $executions;
    }

    /**
     * @return list<string>
     */
    private function targetConnections(?string $connection, bool $all): array
    {
        if ($all) {
            return $this->registry->connectionNames();
        }

        $target = $connection ?? 'default';

        if (!$this->registry->hasConnection($target)) {
            throw new SeederConnectionNotRegisteredException($target);
        }

        return [$target];
    }

    private function databaseConnection(string $connection): Connection
    {
        if ($connection === 'default') {
            return $this->database->default();
        }

        return $this->database->connection($connection);
    }
}
