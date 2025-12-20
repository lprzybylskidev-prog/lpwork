<?php
declare(strict_types=1);

namespace Migrations\DefaultConnection;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates migrations metadata table.
 */
final class V00000000000000CreateMigrationsTable extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return "Create migrations metadata table";
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable("migrations")) {
            return;
        }

        $table = $schema->createTable("migrations");
        $table->addColumn("version", "string", ["length" => 191]);
        $table->addColumn("executed_at", "datetime", ["notnull" => false]);
        $table->addColumn("execution_time", "integer", ["notnull" => false]);
        $table->setPrimaryKey(["version"]);
    }

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("migrations")) {
            $schema->dropTable("migrations");
        }
    }
}
