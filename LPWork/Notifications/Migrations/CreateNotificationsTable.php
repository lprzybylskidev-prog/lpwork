<?php

declare(strict_types=1);

namespace LPWork\Notifications\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use LPWork\Database\SqlIdentifier;

/**
 * Represents the create notifications table framework component.
 */
final class CreateNotificationsTable implements Migration
{
    private readonly string $table;

    /**
     * Creates a new CreateNotificationsTable instance.
     */
    public function __construct(string $table = 'notifications')
    {
        $this->table = SqlIdentifier::table($table);
    }

    /**
     * Performs the up operation.
     */
    public function up(Connection $db): void
    {
        new Schema($db)->create($this->table(), static function (Table $table): void {
            $table->primaryString('id')
                ->string('notifiable_type')
                ->string('notifiable_id')
                ->string('notification_class')
                ->text('data')
                ->nullableInteger('read_at')
                ->integer('created_at')
                ->integer('updated_at');
        });
    }

    /**
     * Performs the down operation.
     */
    public function down(Connection $db): void
    {
        new Schema($db)->drop($this->table());
    }

    private function table(): string
    {
        return $this->table;
    }
}
