<?php

declare(strict_types=1);

namespace LPWork\DebugBar\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\DebugBar\DebugBarController;
use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\DebugBar\DebugBarRenderer;
use LPWork\DebugBar\DebugBarRequestStore;
use LPWork\DebugBar\DebugBarResponseInjector;
use LPWork\Foundation\Application;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\ServiceProvider;
use LPWork\Observability\DiagnosticsSnapshotFactory;

/**
 * Registers debug bar service provider services with the framework container.
 */
final class DebugBarServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(DebugBarRequestStore::class, static function (Container $container): DebugBarRequestStore {
            $app = $container->make(Application::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            return new DebugBarRequestStore($app->basePath('storage/framework/debugbar'));
        });
        $container->singleton(DebugBarRenderer::class, static function (Container $container): DebugBarRenderer {
            $metadata = $container->make(FrameworkMetadata::class);

            if (!$metadata instanceof FrameworkMetadata) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(FrameworkMetadata::class);
            }

            return new DebugBarRenderer($metadata);
        });
        $container->singleton(DebugBarController::class, static function (Container $container): DebugBarController {
            $store = $container->make(DebugBarRequestStore::class);

            if (!$store instanceof DebugBarRequestStore) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarRequestStore::class);
            }

            return new DebugBarController($store, Config::getBool('app.debug'));
        });
        $container->singleton(DebugBarResponseInjector::class, static function (Container $container): DebugBarResponseInjector {
            $snapshots = $container->make(DiagnosticsSnapshotFactory::class);
            $renderer = $container->make(DebugBarRenderer::class);
            $store = $container->make(DebugBarRequestStore::class);

            if (!$snapshots instanceof DiagnosticsSnapshotFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DiagnosticsSnapshotFactory::class);
            }

            if (!$renderer instanceof DebugBarRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarRenderer::class);
            }

            if (!$store instanceof DebugBarRequestStore) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarRequestStore::class);
            }

            return new DebugBarResponseInjector($snapshots, $renderer, $store, Config::getBool('app.debug'));
        });
        $container->singleton(DebugBarPageRenderer::class, static function (Container $container): DebugBarPageRenderer {
            $snapshots = $container->make(DiagnosticsSnapshotFactory::class);
            $renderer = $container->make(DebugBarRenderer::class);
            $store = $container->make(DebugBarRequestStore::class);

            if (!$snapshots instanceof DiagnosticsSnapshotFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DiagnosticsSnapshotFactory::class);
            }

            if (!$renderer instanceof DebugBarRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarRenderer::class);
            }

            if (!$store instanceof DebugBarRequestStore) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarRequestStore::class);
            }

            return new DebugBarPageRenderer($snapshots, $renderer, $store, Config::getBool('app.debug'));
        });
    }
}
