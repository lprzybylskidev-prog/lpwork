<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Console\Provider\BuiltinCommandProvider;
use LPwork\Kernel\CliKernel;
use LPwork\Provider\Contract\ProviderInterface;
use Config\CommandProvider as AppCommandProvider;

/**
 * Registers CLI-specific services for the CLI runtime.
 */
class CliProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CliKernel::class => \DI\autowire(CliKernel::class),
            BuiltinCommandProvider::class => \DI\autowire(
                BuiltinCommandProvider::class,
            ),
            AppCommandProvider::class => \DI\autowire(
                AppCommandProvider::class,
            ),
        ]);
    }
}
