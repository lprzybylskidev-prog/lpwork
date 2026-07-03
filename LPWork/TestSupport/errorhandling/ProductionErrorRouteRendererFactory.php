<?php

declare(strict_types=1);

namespace Tests\support\errorhandling;

use LPWork\ErrorHandling\Renderers\HttpProductionErrorRouteRenderer;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\Kernels\Http\ControllerDispatcher;
use LPWork\Routing\Router;
use Tests\support\ApplicationFactory;

final readonly class ProductionErrorRouteRendererFactory
{
    public static function make(Router $router): HttpProductionErrorRouteRenderer
    {
        return new HttpProductionErrorRouteRenderer(
            router: $router,
            dispatcher: new ControllerDispatcher(ApplicationFactory::create()),
            fallbackRenderer: new HttpProductionExceptionRenderer(),
        );
    }
}
