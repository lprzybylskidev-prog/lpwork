<?php

declare(strict_types=1);

namespace LPWork\Cache\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use LPWork\Database\SqlIdentifier;

/**
 * Represents the create cache entries table framework component.
 */
final readonly class CreateCacheEntriesTable implements Migration
{
    private string $table;

    /**
     * Creates a new CreateCacheEntriesTable instance.
     */
    public function __construct(string $table = 'cache_entries')
    {
        $this->table = SqlIdentifier::table($table);
    }

    /**
     * Performs the up operation.
     */
    public function up(Connection $db): void
    {
        new Schema($db)->create($this->table, static function (Table $table): void {
            $table->primaryString('cache_key', 255)
                ->text('value')
                ->nullableInteger('expires_at')
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
