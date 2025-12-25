<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Provider\Http\HttpKernelModuleProvider;
use LPwork\Provider\Http\HttpRoutingModuleProvider;
use LPwork\Provider\Contract\ProviderInterface;

/**
 * Registers HTTP-specific services for the HTTP runtime.
 */
class HttpProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        (new HttpRoutingModuleProvider())->register($containerBuilder);
        (new HttpKernelModuleProvider())->register($containerBuilder);
    }
}
