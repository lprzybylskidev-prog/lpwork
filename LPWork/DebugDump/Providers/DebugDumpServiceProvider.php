<?php

declare(strict_types=1);

namespace LPWork\DebugDump\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\DebugDump\Debug;
use LPWork\DebugDump\DebugDumper;
use LPWork\DebugDump\DebugDumpExceptionResponseFactory;
use LPWork\DebugDump\DebugDumpInspector;
use LPWork\DebugDump\DebugDumpRenderer;
use LPWork\DebugDump\DebugDumpResponseInjector;
use LPWork\DebugDump\DebugDumpStore;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers debug dump service provider services with the framework container.
 */
final class DebugDumpServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(DebugDumpInspector::class);
        $container->singleton(DebugDumpStore::class);
        $container->singleton(DebugDumpRenderer::class);
        $container->singleton(DebugDumper::class, static function (Container $container): DebugDumper {
            $inspector = $container->make(DebugDumpInspector::class);
            $store = $container->make(DebugDumpStore::class);

            if (!$inspector instanceof DebugDumpInspector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugDumpInspector::class);
            }

            if (!$store instanceof DebugDumpStore) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugDumpStore::class);
            }

            return new DebugDumper($inspector, $store, Config::getBool('app.debug'));
        });
        $container->singleton(DebugDumpResponseInjector::class, static function (Container $container): DebugDumpResponseInjector {
            $store = $container->make(DebugDumpStore::class);
            $renderer = $container->make(DebugDumpRenderer::class);

            if (!$store instanceof DebugDumpStore) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugDumpStore::class);
            }

            if (!$renderer instanceof DebugDumpRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugDumpRenderer::class);
            }

            return new DebugDumpResponseInjector($store, $renderer, Config::getBool('app.debug'));
        });
        $container->singleton(DebugDumpExceptionResponseFactory::class, static function (Container $container): DebugDumpExceptionResponseFactory {
            $renderer = $container->make(DebugDumpRenderer::class);
            $debugBar = $container->isBound(DebugBarPageRenderer::class)
                ? $container->make(DebugBarPageRenderer::class)
                : null;

            if (!$renderer instanceof DebugDumpRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugDumpRenderer::class);
            }

            if ($debugBar !== null && !$debugBar instanceof DebugBarPageRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugBarPageRenderer::class);
            }

            return new DebugDumpExceptionResponseFactory($renderer, $debugBar);
        });

        $dumper = $container->make(DebugDumper::class);

        if (!$dumper instanceof DebugDumper) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(DebugDumper::class);
        }

        Debug::setDumper($dumper);
    }
}
