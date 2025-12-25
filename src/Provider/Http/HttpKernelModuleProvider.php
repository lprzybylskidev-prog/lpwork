<?php
declare(strict_types=1);

namespace LPwork\Provider\Http;

use DI\ContainerBuilder;
use LPwork\Kernel\HttpKernel;

/**
 * Registers HTTP kernel.
 */
final class HttpKernelModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            HttpKernel::class => \DI\autowire(HttpKernel::class),
        ]);
    }
}
