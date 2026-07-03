<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;

final class CreateMigrationEventsTable implements Migration
{
    public function up(Connection $db): void
    {
        $db->statement('create table migration_events (name text not null)');
    }

    public function down(Connection $db): void
    {
        $db->statement('drop table migration_events');
    }
}
