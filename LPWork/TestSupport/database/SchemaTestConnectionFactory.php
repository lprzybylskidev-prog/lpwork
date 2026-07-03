<?php

declare(strict_types=1);

namespace Tests\support\database;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;

final readonly class SchemaTestConnectionFactory
{
    public function __construct(
        private string $basePath,
    ) {}

    public function create(): Connection
    {
        return new DatabaseManager(
            [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
            $this->basePath,
        )->default();
    }
}
