<?php
declare(strict_types=1);

namespace LPwork\Provider;

use DI\ContainerBuilder;
use LPwork\Http\Middleware\MiddlewareProvider as BuiltinMiddlewareProvider;
use LPwork\Http\Middleware\Contract\MiddlewareProviderInterface;
use LPwork\Http\Middleware\ErrorHandlingMiddleware;
use LPwork\Http\Middleware\Routing\RouteDispatchMiddleware;
use LPwork\Http\Middleware\Routing\RouteMatchMiddleware;
use LPwork\Http\Routing\FastRouteDispatcherFactory;
use LPwork\Http\Routing\RouteLoader;
use LPwork\Kernel\HttpKernel;
use LPwork\Provider\Contract\ProviderInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Config\MiddlewareProvider as AppMiddlewareProvider;
use FastRoute\Dispatcher;

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
            MiddlewareProviderInterface::class => \DI\get(
                AppMiddlewareProvider::class,
            ),
            BuiltinMiddlewareProvider::class => \DI\autowire(
                BuiltinMiddlewareProvider::class,
            ),
            AppMiddlewareProvider::class => \DI\autowire(
                AppMiddlewareProvider::class,
            ),
            RouteLoader::class => \DI\autowire(RouteLoader::class)->constructor(
                \dirname(__DIR__, 2) . "/config/routes/routes.php",
                \dirname(__DIR__) . "/Http/Routes/routes.php",
            ),
            FastRouteDispatcherFactory::class => \DI\autowire(
                FastRouteDispatcherFactory::class,
            ),
            Dispatcher::class => \DI\factory(static function (
                RouteLoader $loader,
                FastRouteDispatcherFactory $factory,
            ): Dispatcher {
                $routes = $loader->load();

                return $factory->create($routes);
            }),
            ErrorHandlingMiddleware::class => \DI\autowire(
                ErrorHandlingMiddleware::class,
            ),
            RouteMatchMiddleware::class => \DI\autowire(
                RouteMatchMiddleware::class,
            ),
            RouteDispatchMiddleware::class => \DI\autowire(
                RouteDispatchMiddleware::class,
            ),
            Psr17Factory::class => \DI\create(Psr17Factory::class),
            ServerRequestCreator::class => \DI\factory(static function (
                Psr17Factory $psr17Factory,
            ): ServerRequestCreator {
                return new ServerRequestCreator(
                    $psr17Factory,
                    $psr17Factory,
                    $psr17Factory,
                    $psr17Factory,
                );
            }),
        ]);
    }
}
