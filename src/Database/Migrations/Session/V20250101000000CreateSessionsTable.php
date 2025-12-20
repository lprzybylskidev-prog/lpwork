<?php
declare(strict_types=1);

namespace Migrations\DefaultConnection\App1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates sessions storage table.
 */
final class V20250101000000CreateSessionsTable extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return "Create sessions table for database session driver";
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable("sessions")) {
            return;
        }

        $table = $schema->createTable("sessions");
        $table->addColumn("id", "string", ["length" => 128]);
        $table->addColumn("payload", "text");
        $table->addColumn("last_activity", "integer");
        $table->addColumn("expires_at", "integer", ["notnull" => false]);
        $table->setPrimaryKey(["id"]);
        $table->addIndex(["expires_at"]);
    }

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("sessions")) {
            $schema->dropTable("sessions");
        }
    }
}
