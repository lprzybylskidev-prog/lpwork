<?php

declare(strict_types=1);

use LPWork\Database\DatabaseManager;
use Tests\support\database\SqliteDatabase;
use Tests\support\testing\Database\ConnectionContract;

it('keeps the sqlite connection compatible with the shared database connection contract', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = new DatabaseManager([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $database->relativePath(),
                ],
            ],
        ], $database->basePath())->default();

        new ConnectionContract($connection)->verifiesQueryAndTransactionBehavior();
    } finally {
        $database->remove();
    }
});
