<?php
declare(strict_types=1);

namespace LPwork\Provider\Http;

use DI\ContainerBuilder;
use LPwork\Kernel\HttpKernel;
use LPwork\Http\Contract\ResponseEmitterInterface;
use LPwork\Http\Response\ResponseEmitter;

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
            ResponseEmitterInterface::class => \DI\autowire(ResponseEmitter::class),
            HttpKernel::class => \DI\autowire(HttpKernel::class),
        ]);
    }
}
