<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders;

/**
 * Represents the seeder execution framework component.
 */
final readonly class SeederExecution
{
    /**
     * Creates a new SeederExecution instance.
     */
    public function __construct(
        public string $connection,
        public string $seeder,
    ) {}
}
