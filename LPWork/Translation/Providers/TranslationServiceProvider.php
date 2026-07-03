<?php

declare(strict_types=1);

namespace LPWork\Translation\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\TranslationHealthCheck;
use LPWork\Translation\Commands\TranslationCacheCommand;
use LPWork\Translation\Commands\TranslationClearCommand;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\TranslationCache;
use LPWork\Translation\TranslationCompiledCache;
use LPWork\Translation\TranslationNamespaceRegistry;
use LPWork\Translation\Translator;

/**
 * Registers translation service provider services with the framework container.
 */
final class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(TranslationNamespaceRegistry::class);
        $container->singleton(TranslationCache::class, static function (Container $container): TranslationCache {
            $app = $container->make(Application::class);
            $namespaces = $container->make(TranslationNamespaceRegistry::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$namespaces instanceof TranslationNamespaceRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(TranslationNamespaceRegistry::class);
            }

            return new TranslationCache($app->basePath(), namespaces: $namespaces);
        });

        $container->singleton(JsonTranslationLoader::class, static function (Container $container): JsonTranslationLoader {
            $app = $container->make(Application::class);
            $cache = $container->make(TranslationCache::class);
            $namespaces = $container->make(TranslationNamespaceRegistry::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$cache instanceof TranslationCache) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(TranslationCache::class);
            }

            if (!$namespaces instanceof TranslationNamespaceRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(TranslationNamespaceRegistry::class);
            }

            $namespaces->add('lpwork', dirname(__DIR__, 2) . '/Foundation/lang');

            return new JsonTranslationLoader($app->basePath('App/Shared/lang'), $cache, $namespaces);
        });

        $container->singleton(Translator::class, static function (Container $container): Translator {
            $loader = $container->make(JsonTranslationLoader::class);

            if (!$loader instanceof JsonTranslationLoader) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(JsonTranslationLoader::class);
            }

            return new Translator(
                loader: $loader,
                locale: Config::getString('app.lang'),
                fallbackLocale: 'en_US',
            );
        });

        $container->singleton(TranslationCompiledCache::class);
        $container->singleton(TranslationCacheCommand::class);
        $container->singleton(TranslationClearCommand::class);
        $container->singleton(TranslationHealthCheck::class);
        $this->registerCompiledCaches($container, [TranslationCompiledCache::class]);

        $this->registerCommands($container, [
            TranslationCacheCommand::class,
            TranslationClearCommand::class,
        ]);
        $this->registerHealthCheck($container, TranslationHealthCheck::class);
    }
}
