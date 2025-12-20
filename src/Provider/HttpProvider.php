<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Kernel\HttpKernel;
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
        $containerBuilder->addDefinitions([
            HttpKernel::class => \DI\autowire(HttpKernel::class),
        ]);
    }
}
