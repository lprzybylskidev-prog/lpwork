<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Http\Error\Contract\DevErrorPageRendererInterface;
use LPwork\Http\Error\DevErrorPageRenderer;
use LPwork\Http\Error\ErrorContextFactory;

/**
 * Registers error rendering services.
 */
final class ErrorHandlingModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ErrorContextFactory::class => \DI\autowire(ErrorContextFactory::class),
            DevErrorPageRendererInterface::class => \DI\autowire(DevErrorPageRenderer::class),
        ]);
    }
}
