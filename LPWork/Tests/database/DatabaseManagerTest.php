<?php

declare(strict_types=1);

use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;
use LPWork\Database\Enums\FetchMode;
use LPWork\Database\Exceptions\DatabaseQueryException;
use LPWork\Database\Exceptions\InvalidDatabaseConfigException;
use LPWork\Database\Exceptions\InvalidDatabaseConnectionException;
use LPWork\Database\Exceptions\UnsupportedDatabaseDriverException;
use LPWork\Database\PdoConnectionFactory;
use Tests\support\database\SqliteDatabase;

it('resolves and caches the default sqlite connection', function (): void {
    $database = SqliteDatabase::create();

    try {
        $manager = sqliteManager($database);

        expect($manager->default())->toBeInstanceOf(Connection::class)
            ->and($manager->default())->toBe($manager->connection('sqlite'))
            ->and($manager->connectionNames())->toBe(['sqlite']);
    } finally {
        $database->remove();
    }
});

it('executes raw statements and selects associative rows by default', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();

        $connection->statement('create table users (id integer primary key, name text not null)');
        $affected = $connection->statement('insert into users (name) values (?)', ['Ada']);

        expect($affected)->toBe(1)
            ->and($connection->select('select id, name from users where name = ?', ['Ada']))
            ->toBe([['id' => 1, 'name' => 'Ada']]);
    } finally {
        $database->remove();
    }
});

it('allows object fetching explicitly', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();
        $connection->statement('create table users (id integer primary key, name text not null)');
        $connection->statement('insert into users (name) values (?)', ['Ada']);

        $rows = $connection->select('select name from users', fetchMode: FetchMode::Object);
        $row = $rows[0];

        if (!$row instanceof stdClass) {
            throw new RuntimeException('Expected object row.');
        }

        expect($row->name)->toBe('Ada');
    } finally {
        $database->remove();
    }
});

it('returns query result helpers and exposes native pdo explicitly', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();
        $connection->statement('create table users (id integer primary key, name text not null)');
        $connection->statement('insert into users (name) values (?)', ['Ada']);

        $result = $connection->query('select name from users');

        expect($result->first())->toBe(['name' => 'Ada'])
            ->and($connection->query('select count(*) from users')->value())->toBe(1)
            ->and($connection->pdo())->toBeInstanceOf(PDO::class);
    } finally {
        $database->remove();
    }
});

it('wraps pdo query failures in database exceptions', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();

        expect(fn(): mixed => $connection->query('select * from missing_table'))
            ->toThrow(DatabaseQueryException::class);
    } finally {
        $database->remove();
    }
});

it('supports manual transactions', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();
        $connection->statement('create table users (name text not null)');

        $connection->beginTransaction();
        $connection->statement('insert into users (name) values (?)', ['Ada']);
        $connection->rollBack();

        expect($connection->query('select count(*) from users')->value())->toBe(0);

        $connection->beginTransaction();
        $connection->statement('insert into users (name) values (?)', ['Grace']);
        $connection->commit();

        expect($connection->query('select count(*) from users')->value())->toBe(1);
    } finally {
        $database->remove();
    }
});

it('rolls back callback transactions when an exception is thrown', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();
        $connection->statement('create table users (name text not null)');

        try {
            $connection->transaction(function (Connection $connection): void {
                $connection->statement('insert into users (name) values (?)', ['Ada']);

                throw new RuntimeException('Stop');
            });
        } catch (RuntimeException) {
        }

        expect($connection->query('select count(*) from users')->value())->toBe(0);
    } finally {
        $database->remove();
    }
});

it('supports nested callback transactions with savepoints', function (): void {
    $database = SqliteDatabase::create();

    try {
        $connection = sqliteManager($database)->default();
        $connection->statement('create table users (name text not null)');

        $connection->transaction(function (Connection $connection): void {
            $connection->statement('insert into users (name) values (?)', ['outer']);

            try {
                $connection->transaction(function (Connection $connection): void {
                    $connection->statement('insert into users (name) values (?)', ['inner']);

                    throw new RuntimeException('Rollback inner');
                });
            } catch (RuntimeException) {
            }
        });

        expect($connection->select('select name from users'))->toBe([['name' => 'outer']]);
    } finally {
        $database->remove();
    }
});

it('throws when a named connection is missing', function (): void {
    $database = SqliteDatabase::create();

    try {
        expect(fn(): Connection => sqliteManager($database)->connection('missing'))
            ->toThrow(InvalidDatabaseConnectionException::class);
    } finally {
        $database->remove();
    }
});

it('builds mysql and pgsql dsns without requiring live drivers', function (): void {
    $factory = new PdoConnectionFactory(\Tests\support\ProjectPaths::root());

    expect($factory->dsn('mysql', 'mysql', [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'lpwork',
        'charset' => 'utf8mb4',
    ]))->toBe('mysql:host=127.0.0.1;port=3306;dbname=lpwork;charset=utf8mb4')
        ->and($factory->dsn('pgsql', 'pgsql', [
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'lpwork',
        ]))->toBe('pgsql:host=127.0.0.1;port=5432;dbname=lpwork');
});

it('validates unsupported drivers and invalid driver options', function (): void {
    $factory = new PdoConnectionFactory(\Tests\support\ProjectPaths::root());

    expect(fn(): string => $factory->dsn('redis', 'redis', []))
        ->toThrow(UnsupportedDatabaseDriverException::class)
        ->and(fn(): string => $factory->dsn('mysql', 'mysql', [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'lpwork',
            'charset' => 'utf8mb4',
        ]))->toThrow(InvalidDatabaseConfigException::class);
});

function sqliteManager(SqliteDatabase $database): DatabaseManager
{
    return new DatabaseManager([
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => $database->relativePath(),
            ],
        ],
    ], $database->basePath());
}
