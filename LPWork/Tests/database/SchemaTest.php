<?php

declare(strict_types=1);

use LPWork\Database\Exceptions\InvalidSqlIdentifierException;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use Tests\support\database\SchemaTestConnectionFactory;

it('creates and drops tables through the schema helper', function (): void {
    $connection = new SchemaTestConnectionFactory(\Tests\support\ProjectPaths::root())->create();
    $schema = new Schema($connection);

    $schema->create('audit_events', static function (Table $table): void {
        $table->primaryString('id')
            ->string('name')
            ->text('payload', nullable: true)
            ->integer('created_at');
    });

    $connection->statement(
        'insert into audit_events (id, name, payload, created_at) values (?, ?, ?, ?)',
        ['evt_1', 'created', null, 1000],
    );

    expect($connection->select('select name, payload, created_at from audit_events'))->toBe([
        [
            'name' => 'created',
            'payload' => null,
            'created_at' => 1000,
        ],
    ]);

    $schema->drop('audit_events');
});

it('validates table and column identifiers before assembling schema sql', function (): void {
    $schema = new Schema(new SchemaTestConnectionFactory(\Tests\support\ProjectPaths::root())->create());

    expect(fn() => $schema->create('bad table', static function (Table $table): void {
        $table->string('name');
    }))->toThrow(InvalidSqlIdentifierException::class);

    expect(fn() => $schema->create('events', static function (Table $table): void {
        $table->string('bad column');
    }))->toThrow(InvalidSqlIdentifierException::class);
});
