<?php
declare(strict_types=1);

namespace LPwork\Provider\Http;

use DI\ContainerBuilder;
use FastRoute\Dispatcher;
use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\Contract\CacheFactoryInterface;
use LPwork\Http\Middleware\BodyParsingMiddleware;
use LPwork\Http\Middleware\Contract\MiddlewareProviderInterface;
use LPwork\Http\Middleware\CorsMiddleware;
use LPwork\Http\Middleware\CsrfMiddleware;
use LPwork\Http\Middleware\ErrorHandlingMiddleware;
use LPwork\Http\Middleware\MiddlewareProvider as BuiltinMiddlewareProvider;
use LPwork\Http\Middleware\Routing\RouteDispatchMiddleware;
use LPwork\Http\Middleware\Routing\RouteMatchMiddleware;
use LPwork\Http\Middleware\SecurityHeadersMiddleware;
use LPwork\Http\Routing\FastRouteDispatcherFactory;
use LPwork\Http\Routing\Contract\RouteLoaderInterface;
use LPwork\Http\Routing\Contract\RouteHandlerResolverInterface;
use LPwork\Http\Routing\Contract\HandlerArgumentResolverInterface;
use LPwork\Http\Routing\RouteHandlerResolver;
use LPwork\Http\Routing\HandlerArgumentResolver;
use LPwork\Http\Routing\RouteLoader;
use LPwork\Http\Url\Contract\UrlGeneratorInterface;
use LPwork\Http\Url\UrlGenerator;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Config\MiddlewareProvider as AppMiddlewareProvider;
use Psr\Container\ContainerInterface;

/**
 * Registers routing, middleware and dispatcher setup for HTTP runtime.
 */
final class HttpRoutingModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            MiddlewareProviderInterface::class => \DI\get(AppMiddlewareProvider::class),
            BuiltinMiddlewareProvider::class => \DI\autowire(BuiltinMiddlewareProvider::class),
            AppMiddlewareProvider::class => \DI\autowire(AppMiddlewareProvider::class),
            RouteLoader::class => \DI\autowire(RouteLoader::class)->constructor(
                \dirname(__DIR__, 3) . '/config/routes/routes.php',
                \dirname(__DIR__, 2) . '/Http/Routes/routes.php',
            ),
            RouteLoaderInterface::class => \DI\get(RouteLoader::class),
            RouteHandlerResolverInterface::class => \DI\autowire(RouteHandlerResolver::class),
            HandlerArgumentResolverInterface::class => \DI\factory(static function (
                ContainerInterface $container,
            ): HandlerArgumentResolverInterface {
                return new HandlerArgumentResolver($container);
            }),
            FastRouteDispatcherFactory::class => \DI\autowire(FastRouteDispatcherFactory::class),
            Dispatcher::class => \DI\factory(static function (
                RouteLoaderInterface $loader,
                FastRouteDispatcherFactory $factory,
                CacheConfiguration $cacheConfiguration,
                CacheFactoryInterface $cacheFactory,
                RedisConnectionManagerInterface $redisConnections,
                DatabaseConnectionManagerInterface $databaseConnections,
            ): Dispatcher {
                $routes = $loader->load();
                $routingCache = $cacheConfiguration->routingCache();
                $enabled = (bool) ($routingCache['enabled'] ?? false);

                if ($enabled) {
                    $poolName = (string) ($routingCache['pool'] ?? 'filesystem');
                    $key = (string) ($routingCache['key'] ?? 'routes:dispatcher');
                    $pool = $cacheFactory->createPool(
                        $poolName,
                        $cacheConfiguration,
                        $redisConnections,
                        $databaseConnections,
                    );
                    $item = $pool->getItem($key);

                    if ($item->isHit()) {
                        $data = $item->get();

                        if (\is_array($data)) {
                            return $factory->createFromData($data);
                        }
                    }

                    $data = $factory->generateData($routes);
                    $item->set($data);
                    $pool->save($item);

                    return $factory->createFromData($data);
                }

                return $factory->create($routes);
            }),
            ErrorHandlingMiddleware::class => \DI\autowire(ErrorHandlingMiddleware::class),
            CorsMiddleware::class => \DI\autowire(CorsMiddleware::class),
            SecurityHeadersMiddleware::class => \DI\autowire(SecurityHeadersMiddleware::class),
            BodyParsingMiddleware::class => \DI\autowire(BodyParsingMiddleware::class),
            CsrfMiddleware::class => \DI\autowire(CsrfMiddleware::class),
            RouteMatchMiddleware::class => \DI\autowire(RouteMatchMiddleware::class),
            RouteDispatchMiddleware::class => \DI\autowire(RouteDispatchMiddleware::class),
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
            UrlGenerator::class => \DI\autowire(UrlGenerator::class),
            UrlGeneratorInterface::class => \DI\get(UrlGenerator::class),
        ]);
    }
}
