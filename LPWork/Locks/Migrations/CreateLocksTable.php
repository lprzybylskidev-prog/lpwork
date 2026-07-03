<?php

declare(strict_types=1);

namespace LPWork\Locks\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use LPWork\Database\SqlIdentifier;

/**
 * Represents the create locks table framework component.
 */
final readonly class CreateLocksTable implements Migration
{
    private string $table;

    /**
     * Creates a new CreateLocksTable instance.
     */
    public function __construct(string $table = 'locks')
    {
        $this->table = SqlIdentifier::table($table);
    }

    /**
     * Performs the up operation.
     */
    public function up(Connection $db): void
    {
        new Schema($db)->create($this->table, static function (Table $table): void {
            $table->primaryString('name', 255)
                ->string('owner', 128)
                ->integer('expires_at')
                ->integer('created_at');
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
