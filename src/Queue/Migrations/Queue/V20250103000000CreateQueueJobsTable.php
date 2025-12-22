<?php
declare(strict_types=1);

namespace Migrations\DefaultConnection\Queue;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates queue jobs table for database queue driver.
 */
final class V20250103000000CreateQueueJobsTable extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Create queue_jobs table for database queue driver';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('queue_jobs')) {
            return;
        }

        $table = $schema->createTable('queue_jobs');
        $table->addColumn('id', 'string', ['length' => 64]);
        $table->addColumn('queue', 'string', ['length' => 64]);
        $table->addColumn('payload', 'text');
        $table->addColumn('attempts', 'integer', ['default' => 0]);
        $table->addColumn('available_at', 'datetime', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['queue']);
        $table->addIndex(['available_at']);
        $table->addIndex(['created_at']);
    }

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('queue_jobs')) {
            $schema->dropTable('queue_jobs');
        }
    }
}
