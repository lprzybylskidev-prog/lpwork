<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Kernel\CliKernel;
use LPwork\Provider\Contract\ProviderInterface;

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
        ]);
    }
}
