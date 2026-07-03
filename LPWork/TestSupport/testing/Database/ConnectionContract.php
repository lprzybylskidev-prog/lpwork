<?php

declare(strict_types=1);

namespace Tests\support\testing\Database;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Enums\FetchMode;
use PHPUnit\Framework\Assert;
use RuntimeException;
use stdClass;

final readonly class ConnectionContract
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function verifiesQueryAndTransactionBehavior(): void
    {
        $this->connection->statement('create table contract_users (id integer primary key, name text not null)');
        $affected = $this->connection->statement('insert into contract_users (name) values (?)', ['Ada']);

        Assert::assertSame(1, $affected);
        Assert::assertSame([['id' => 1, 'name' => 'Ada']], $this->connection->select('select id, name from contract_users where name = ?', ['Ada']));
        Assert::assertSame('Ada', $this->connection->query('select name from contract_users where id = ?', [1])->value());

        $rows = $this->connection->select('select name from contract_users', fetchMode: FetchMode::Object);
        $row = $rows[0] ?? null;

        if (!$row instanceof stdClass) {
            throw new RuntimeException('Expected database connection to support object fetch mode.');
        }

        Assert::assertSame('Ada', $row->name);

        $this->connection->beginTransaction();
        $this->connection->statement('insert into contract_users (name) values (?)', ['Rollback']);
        $this->connection->rollBack();
        Assert::assertSame(1, $this->connection->query('select count(*) from contract_users')->value());

        $this->connection->transaction(function (Connection $connection): void {
            $connection->statement('insert into contract_users (name) values (?)', ['Grace']);
        });

        Assert::assertSame(2, $this->connection->query('select count(*) from contract_users')->value());
    }
}
