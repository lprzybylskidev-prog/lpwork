<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;

/**
 * Placeholder for console-related bindings shared across runtimes.
 */
final class ConsoleModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        // No shared console services to register at this time.
    }
}
