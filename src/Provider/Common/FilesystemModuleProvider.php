<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Filesystem\FilesystemManager;

/**
 * Registers filesystem manager.
 */
final class FilesystemModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FilesystemManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): FilesystemManager {
                $disks = $config->get('filesystem.disks', []);
                $default = $config->getString('filesystem.default_disk', 'local');

                return new FilesystemManager($disks, $default);
            }),
        ]);
    }
}
