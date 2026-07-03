<?php

declare(strict_types=1);

namespace LPWork\Session\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use LPWork\Database\SqlIdentifier;

/**
 * Represents the create sessions table framework component.
 */
final readonly class CreateSessionsTable implements Migration
{
    private string $table;

    /**
     * Creates a new CreateSessionsTable instance.
     */
    public function __construct(string $table = 'sessions')
    {
        $this->table = SqlIdentifier::table($table);
    }

    /**
     * Performs the up operation.
     */
    public function up(Connection $db): void
    {
        new Schema($db)->create($this->table, static function (Table $table): void {
            $table->primaryString('id', 128)
                ->text('payload')
                ->integer('expires_at')
                ->integer('updated_at');
        });
    }

    /**
     * Performs the down operation.
     */
    public function down(Connection $db): void
    {
        new Schema($db)->drop($this->table);
    }
}
