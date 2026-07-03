<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Migrations\Exceptions\DuplicateMigrationException;

/**
 * Stores and resolves migration registry registrations.
 */
final class MigrationRegistry
{
    /**
     * @var array<string, list<class-string<Migration>>>
     */
    private array $migrations = [];

    /**
     * @param list<class-string<Migration>> $migrations
     */
    public function add(string $connection, array $migrations): void
    {
        foreach ($migrations as $migration) {
            if (in_array($migration, $this->migrations[$connection] ?? [], true)) {
                throw new DuplicateMigrationException($connection, $migration);
            }

            $this->migrations[$connection][] = $migration;
        }
    }

    /**
     * @return list<string>
     */
    public function connectionNames(): array
    {
        return array_keys($this->migrations);
    }

    /**
     * @return list<class-string<Migration>>
     */
    public function forConnection(string $connection): array
    {
        return $this->migrations[$connection] ?? [];
    }

    /**
     * Reports whether has connection.
     */
    public function hasConnection(string $connection): bool
    {
        return array_key_exists($connection, $this->migrations);
    }
}
