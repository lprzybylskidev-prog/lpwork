<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Contracts;

use LPWork\Database\Contracts\Connection;

/**
 * Defines the contract for seeder.
 */
interface Seeder
{
    /**
     * Runs run.
     */
    public function run(Connection $db): void;
}
