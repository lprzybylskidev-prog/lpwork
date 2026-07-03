<?php

declare(strict_types=1);

namespace LPWork\Schedule\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Schema\Schema;
use LPWork\Database\Schema\Table;
use LPWork\Database\SqlIdentifier;

/**
 * Represents the create schedule runs table framework component.
 */
final class CreateScheduleRunsTable implements Migration
{
    private readonly string $table;

    /**
     * Creates a new CreateScheduleRunsTable instance.
     */
    public function __construct(string $table = 'schedule_runs')
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
                ->string('task_name')
                ->string('task_type', 32)
                ->string('target')
                ->string('status', 32)
                ->nullableInteger('exit_code')
                ->text('message', nullable: true)
                ->integer('started_at')
                ->integer('finished_at')
                ->integer('created_at');
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
