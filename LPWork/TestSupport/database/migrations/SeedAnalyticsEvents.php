<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Seeders\Contracts\Seeder;

final class SeedAnalyticsEvents implements Seeder
{
    public function run(Connection $db): void
    {
        $db->statement('insert into analytics_events (name) values (?)', ['seeded-analytics']);
    }
}
