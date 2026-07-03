<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;

final class CreateAnalyticsEventsTable implements Migration
{
    public function up(Connection $db): void
    {
        $db->statement('create table analytics_events (name text not null)');
        $db->statement('insert into analytics_events (name) values (?)', ['analytics']);
    }

    public function down(Connection $db): void
    {
        $db->statement('drop table analytics_events');
    }
}
