<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders;

use LPWork\Database\Seeders\Contracts\Seeder;
use LPWork\Database\Seeders\Exceptions\DuplicateSeederException;

/**
 * Stores and resolves seeder registry registrations.
 */
final class SeederRegistry
{
    /**
     * @var array<string, list<class-string<Seeder>>>
     */
    private array $seeders = [];

    /**
     * @param list<class-string<Seeder>> $seeders
     */
    public function add(string $connection, array $seeders): void
    {
        foreach ($seeders as $seeder) {
            if (in_array($seeder, $this->seeders[$connection] ?? [], true)) {
                throw new DuplicateSeederException($connection, $seeder);
            }

            $this->seeders[$connection][] = $seeder;
        }
    }

    /**
     * @return list<string>
     */
    public function connectionNames(): array
    {
        return array_keys($this->seeders);
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public function forConnection(string $connection): array
    {
        return $this->seeders[$connection] ?? [];
    }

    /**
     * Reports whether has connection.
     */
    public function hasConnection(string $connection): bool
    {
        return array_key_exists($connection, $this->seeders);
    }
}
