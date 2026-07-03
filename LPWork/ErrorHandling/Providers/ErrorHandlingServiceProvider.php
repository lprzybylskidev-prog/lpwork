<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Providers;

use LPWork\Cache\CacheDebugCollector;
use LPWork\Cache\CacheDebugContextProvider;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Context\HttpRequestDebugContextProvider;
use LPWork\ErrorHandling\Context\MiddlewareDebugContextProvider;
use LPWork\ErrorHandling\Context\RouteDebugContextProvider;
use LPWork\ErrorHandling\Context\SessionDebugContextProvider;
use LPWork\ErrorHandling\Contracts\ExceptionRenderer;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\ErrorHandler;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Renderers\CliExceptionRenderer;
use LPWork\ErrorHandling\Renderers\DebugExceptionPageRenderer;
use LPWork\ErrorHandling\Renderers\HttpDebugExceptionRenderer;
use LPWork\ErrorHandling\Renderers\HttpProductionErrorRouteRenderer;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\ErrorHandling\Renderers\JsonHttpExceptionRenderer;
use LPWork\ErrorHandling\Reporters\LoggingExceptionReporter;
use LPWork\Foundation\Application;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\ServiceProvider;
use LPWork\Logging\LogManager;
use LPWork\Observability\DiagnosticsSnapshotFactory;

/**
 * Registers error handling service provider services with the framework container.
 */
final class ErrorHandlingServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(ErrorHandler::class);
        $container->singleton(HttpDebugContext::class, static function (Container $container): HttpDebugContext {
            $context = new HttpDebugContext();
            $context->addProvider(new HttpRequestDebugContextProvider());
            $context->addProvider(new RouteDebugContextProvider());
            $context->addProvider(new MiddlewareDebugContextProvider());
            $context->addProvider(new SessionDebugContextProvider());

            if ($container->has(CacheDebugCollector::class)) {
                $collector = $container->make(CacheDebugCollector::class);

                if ($collector instanceof CacheDebugCollector) {
                    $context->addProvider(new CacheDebugContextProvider($collector));
                }
            }

            return $context;
        });
        $container->singleton(CliExceptionRenderer::class, static fn(): CliExceptionRenderer => new CliExceptionRenderer(Config::getBool('app.debug')));
        $container->singleton(ExceptionRenderer::class, static function (Container $container): ExceptionRenderer {
            $renderer = $container->make(CliExceptionRenderer::class);

            if (!$renderer instanceof ExceptionRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ExceptionRenderer::class);
            }

            return $renderer;
        });
        $container->singleton(HttpProductionExceptionRenderer::class);
        $container->singleton(HttpProductionErrorRouteRenderer::class, static function (Container $container): HttpProductionErrorRouteRenderer {
            $route = self::productionErrorRoute();

            if ($route === null) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(HttpProductionErrorRouteRenderer::class);
            }

            $renderer = $container->make(HttpProductionExceptionRenderer::class);
            $router = $container->make(\LPWork\Routing\Router::class);
            $dispatcher = $container->make(\LPWork\Kernels\Http\ControllerDispatcher::class);

            if (!$renderer instanceof HttpProductionExceptionRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(HttpProductionExceptionRenderer::class);
            }

            if (!$router instanceof \LPWork\Routing\Router) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(\LPWork\Routing\Router::class);
            }

            if (!$dispatcher instanceof \LPWork\Kernels\Http\ControllerDispatcher) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(\LPWork\Kernels\Http\ControllerDispatcher::class);
            }

            return new HttpProductionErrorRouteRenderer($router, $dispatcher, $renderer, $route);
        });
        $container->singleton(JsonHttpExceptionRenderer::class);

        $container->singleton(HttpDebugExceptionRenderer::class, static function (Container $container): HttpDebugExceptionRenderer {
            $app = $container->make(Application::class);
            $context = $container->make(HttpDebugContext::class);
            $metadata = $container->make(FrameworkMetadata::class);
            $snapshots = $container->has(DiagnosticsSnapshotFactory::class)
                ? $container->make(DiagnosticsSnapshotFactory::class)
                : null;
            $debugBar = $container->isBound(DebugBarPageRenderer::class)
                ? $container->make(DebugBarPageRenderer::class)
                : null;

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$context instanceof HttpDebugContext) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(HttpDebugContext::class);
            }

            if (!$metadata instanceof FrameworkMetadata) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(FrameworkMetadata::class);
            }

            if ($snapshots !== null && !$snapshots instanceof DiagnosticsSnapshotFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DiagnosticsSnapshotFactory::class);
            }

            if ($debugBar !== null && !$debugBar instanceof DebugBarPageRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarPageRenderer::class);
            }

            return new HttpDebugExceptionRenderer(
                $app->basePath(),
                $context,
                $snapshots,
                new DebugExceptionPageRenderer(metadata: $metadata),
                $debugBar,
            );
        });

        $container->singleton(ExceptionReporter::class, static function (Container $container): ExceptionReporter {
            $manager = $container->make(LogManager::class);

            if (!$manager instanceof LogManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(LogManager::class);
            }

            return new LoggingExceptionReporter($manager->channel('error'));
        });

        $container->singleton(HttpExceptionRenderer::class, static function (Container $container): HttpExceptionRenderer {
            if (Config::getBool('app.debug')) {
                $renderer = $container->make(HttpDebugExceptionRenderer::class);
            } else {
                $route = self::productionErrorRoute();
                $renderer = $route === null
                    ? $container->make(HttpProductionExceptionRenderer::class)
                    : $container->make(HttpProductionErrorRouteRenderer::class);
            }

            if (!$renderer instanceof HttpExceptionRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(HttpExceptionRenderer::class);
            }

            return $renderer;
        });

        $container->singleton(CliExceptionHandler::class);
        $container->singleton(HttpExceptionHandler::class);
    }

    private static function productionErrorRoute(): ?string
    {
        $route = Config::get('error.production_route', null);

        return is_string($route) && $route !== '' ? $route : null;
    }
}
