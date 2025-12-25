<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Version\FrameworkVersion;

/**
 * Registers framework version service.
 */
final class VersionModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FrameworkVersion::class => \DI\autowire(FrameworkVersion::class),
        ]);
    }
}
