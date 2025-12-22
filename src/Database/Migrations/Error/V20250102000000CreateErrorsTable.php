<?php
declare(strict_types=1);

namespace Migrations\DefaultConnection\App2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates error log table for database error log driver.
 */
final class V20250102000000CreateErrorsTable extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Create errors table for error log';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('errors')) {
            return;
        }

        $table = $schema->createTable('errors');
        $table->addColumn('id', 'string', ['length' => 64]);
        $table->addColumn('level', 'string', ['length' => 32]);
        $table->addColumn('code', 'integer', ['notnull' => false]);
        $table->addColumn('message', 'text');
        $table->addColumn('exception_class', 'string', ['length' => 255]);
        $table->addColumn('file', 'string', ['length' => 255]);
        $table->addColumn('line', 'integer');
        $table->addColumn('trace', 'text');
        $table->addColumn('context', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at']);
        $table->addIndex(['level']);
    }

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('errors')) {
            $schema->dropTable('errors');
        }
    }
}
