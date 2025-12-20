<?php
declare(strict_types=1);

namespace LPwork\Database\Migration;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\Migration\Contract\MigrationProviderInterface;

/**
 * Provides framework migrations grouped by connection.
 */
class FrameworkMigrationProvider implements MigrationProviderInterface
{
    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $config;

    /**
     * @param ConfigRepositoryInterface $config
     */
    public function __construct(ConfigRepositoryInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getMigrationPaths(): array
    {
        $paths = [\dirname(__DIR__) . "/Migrations"];
        $sessionDriver = $this->config->getString("session.driver", "php");

        if ($sessionDriver === "database") {
            $paths[] = \dirname(__DIR__) . "/Migrations/Session";
        }

        return [
            "default" => $paths,
        ];
    }
}
