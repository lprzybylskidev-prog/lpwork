<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Contracts;

use LPWork\Database\Contracts\Connection;

/**
 * Defines the contract for migration.
 */
interface Migration
{
    /**
     * Performs the up operation.
     */
    public function up(Connection $db): void;

    /**
     * Performs the down operation.
     */
    public function down(Connection $db): void;
}
