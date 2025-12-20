<?php
declare(strict_types=1);

namespace LPwork\Database\Seeder\Contract;

/**
 * Represents a database seeder.
 */
interface SeederInterface
{
    /**
     * Seeds the database.
     *
     * @return void
     */
    public function run(): void;
}
