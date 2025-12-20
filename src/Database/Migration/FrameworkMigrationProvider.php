<?php
declare(strict_types=1);

namespace LPwork\Database\Migration;

use LPwork\Database\Migration\Contract\MigrationProviderInterface;

/**
 * Provides framework migrations grouped by connection.
 */
class FrameworkMigrationProvider implements MigrationProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getMigrationPaths(): array
    {
        return [
            "default" => [\dirname(__DIR__) . "/Migrations"],
        ];
    }
}
