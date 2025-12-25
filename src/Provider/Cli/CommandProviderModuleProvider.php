<?php
declare(strict_types=1);

namespace LPwork\Provider\Cli;

use Config\CommandProvider as AppCommandProvider;
use DI\ContainerBuilder;
use LPwork\Console\Provider\BuiltinCommandProvider;

/**
 * Registers command providers for CLI runtime.
 */
final class CommandProviderModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            BuiltinCommandProvider::class => \DI\autowire(BuiltinCommandProvider::class),
            AppCommandProvider::class => \DI\autowire(AppCommandProvider::class),
        ]);
    }
}
