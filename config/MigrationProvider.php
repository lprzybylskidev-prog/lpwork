<?php
declare(strict_types=1);

namespace Config;

use LPwork\Database\Migration\Contract\MigrationProviderInterface;

/**
 * Application-level migration provider.
 */
class MigrationProvider implements MigrationProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getMigrationPaths(): array
    {
        return [
            'default' => [\dirname(__DIR__) . '/database/migrations'],
            // "other_connection" => [...],
        ];
    }
}
