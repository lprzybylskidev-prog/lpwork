<?php
declare(strict_types=1);

namespace LPwork\Provider\Cli;

use DI\ContainerBuilder;
use LPwork\Kernel\CliKernel;

/**
 * Registers CLI kernel.
 */
final class CliKernelModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CliKernel::class => \DI\autowire(CliKernel::class),
        ]);
    }
}
