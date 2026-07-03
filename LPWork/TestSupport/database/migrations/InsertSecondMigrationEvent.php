<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;

final class InsertSecondMigrationEvent implements Migration
{
    public function up(Connection $db): void
    {
        $db->statement('insert into migration_events (name) values (?)', ['second']);
    }

    public function down(Connection $db): void
    {
        $db->statement('delete from migration_events where name = ?', ['second']);
    }
}
