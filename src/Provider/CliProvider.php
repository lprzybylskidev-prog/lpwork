<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Provider\Cli\CliKernelModuleProvider;
use LPwork\Provider\Cli\CommandProviderModuleProvider;
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
        (new CliKernelModuleProvider())->register($containerBuilder);
        (new CommandProviderModuleProvider())->register($containerBuilder);
    }
}
