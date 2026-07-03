<?php

declare(strict_types=1);

namespace LPWork\Queue\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use LPWork\Database\SqlIdentifier;

/**
 * Represents the create queue jobs table framework component.
 */
final class CreateQueueJobsTable implements Migration
{
    private readonly string $table;

    /**
     * Creates a new CreateQueueJobsTable instance.
     */
    public function __construct(string $table = 'queue_jobs')
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
                ->string('queue')
                ->string('job_class')
                ->text('payload')
                ->string('status', 32)
                ->integer('attempts')
                ->integer('max_attempts')
                ->integer('available_at')
                ->nullableInteger('reserved_until')
                ->nullableInteger('completed_at')
                ->nullableInteger('failed_at')
                ->text('last_error', nullable: true)
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
