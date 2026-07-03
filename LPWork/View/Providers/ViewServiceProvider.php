<?php

declare(strict_types=1);

namespace LPWork\View\Providers;

use LPWork\Cache\CacheManager;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\ViewHealthCheck;
use LPWork\Http\ViewRenderer as HttpViewRenderer;
use LPWork\Observability\MetricCollector;
use LPWork\Translation\Translator;
use LPWork\View\Commands\ViewClearCommand;
use LPWork\View\Context\ViewDebugContextProvider;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\Exceptions\InvalidViewConfigException;
use LPWork\View\Exceptions\MissingViewConfigException;
use LPWork\View\PhpViewEngine;
use LPWork\View\PhpViewEngineExtensions;
use LPWork\View\ViewDebugCollector;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;
use LPWork\View\ViewNamespaceRegistry;

/**
 * Registers view service provider services with the framework container.
 */
final class ViewServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(PhpViewEngineExtensions::class);
        $container->singleton(ViewNamespaceRegistry::class);
        $this->registerFrameworkViewNamespaces($container);
        $container->singleton(ViewDebugCollector::class, static function (Container $container): ViewDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new ViewDebugCollector($metrics);
        });
        $container->singleton(PhpViewEngine::class, static function (Container $container): PhpViewEngine {
            $filesystem = $container->make(Filesystem::class);
            $extensions = $container->make(PhpViewEngineExtensions::class);

            if (!$filesystem instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            if (!$extensions instanceof PhpViewEngineExtensions) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(PhpViewEngineExtensions::class);
            }

            return new PhpViewEngine($filesystem, $extensions);
        });
        $container->singleton(ViewEngine::class, static function (Container $container): ViewEngine {
            $engine = $container->make(PhpViewEngine::class);

            if (!$engine instanceof ViewEngine) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewEngine::class);
            }

            return $engine;
        });

        $container->singleton(ViewFinder::class, static function (Container $container): ViewFinder {
            $app = $container->make(Application::class);
            $cache = $container->make(CacheManager::class);
            $namespaces = $container->make(ViewNamespaceRegistry::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$cache instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            if (!$namespaces instanceof ViewNamespaceRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewNamespaceRegistry::class);
            }

            $reader = self::reader();

            return new ViewFinder(
                paths: $reader->stringList('paths'),
                basePath: $app->basePath(),
                cache: $cache->store($reader->string('cache_store')),
                extension: $reader->string('extension'),
                namespaces: $namespaces,
            );
        });

        $container->singleton(ViewFactory::class, static function (Container $container): ViewFactory {
            $finder = $container->make(ViewFinder::class);
            $engine = $container->make(ViewEngine::class);
            try {
                $translator = $container->make(Translator::class);
            } catch (CannotResolveDependencyException) {
                $translator = null;
            }
            $collector = $container->make(ViewDebugCollector::class);

            if (!$finder instanceof ViewFinder) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewFinder::class);
            }

            if (!$engine instanceof ViewEngine) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewEngine::class);
            }

            if ($translator !== null && !$translator instanceof Translator) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Translator::class);
            }

            if (!$collector instanceof ViewDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewDebugCollector::class);
            }

            return new ViewFactory($finder, $engine, $translator, $collector);
        });

        $container->singleton(HttpViewRenderer::class);
        $container->singleton(ViewHealthCheck::class);
        $container->singleton(ViewClearCommand::class, static function (Container $container): ViewClearCommand {
            $cache = $container->make(CacheManager::class);

            if (!$cache instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            return new ViewClearCommand($cache, self::reader()->string('cache_store'));
        });

        $this->registerCommands($container, [ViewClearCommand::class]);
        $this->registerHealthCheck($container, ViewHealthCheck::class);
        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): ViewDebugContextProvider {
                $collector = $container->make(ViewDebugCollector::class);

                if (!$collector instanceof ViewDebugCollector) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewDebugCollector::class);
                }

                return new ViewDebugContextProvider($collector);
            },
        );
    }

    private static function reader(): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: Config::getArray('view'),
            missingException: static fn(string $key): MissingViewConfigException => new MissingViewConfigException($key),
            invalidException: static fn(string $key): InvalidViewConfigException => new InvalidViewConfigException($key),
        );
    }

    private function registerFrameworkViewNamespaces(Container $container): void
    {
        $namespaces = $container->make(ViewNamespaceRegistry::class);

        if (!$namespaces instanceof ViewNamespaceRegistry) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(ViewNamespaceRegistry::class);
        }

        $namespaces->add('lpwork', dirname(__DIR__) . '/Resources/views');
    }
}
