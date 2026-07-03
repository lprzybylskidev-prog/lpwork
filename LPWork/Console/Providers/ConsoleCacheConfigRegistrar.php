<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Cache\CacheClearer;
use LPWork\Config\ConfigCache;
use LPWork\Config\ConfigCacheRebuilder;
use LPWork\Config\ConfigCompiledCache;
use LPWork\Config\ConfigShowRenderer;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Config\EnvironmentConfigurationValidator;
use LPWork\Config\EnvironmentRequirementRegistry;
use LPWork\Config\EnvironmentValidationRenderer;
use LPWork\Console\Commands\CacheClearCommand;
use LPWork\Console\Commands\ConfigCacheCommand;
use LPWork\Console\Commands\ConfigClearCommand;
use LPWork\Console\Commands\ConfigShowCommand;
use LPWork\Console\Commands\ConfigValidateCommand;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\CompiledCacheRegistry;

/**
 * Registers console services that inspect, rebuild, validate, or clear framework caches and configuration.
 */
final readonly class ConsoleCacheConfigRegistrar implements ConsoleServiceRegistrar
{
    /**
     * Adds cache and configuration command dependencies to the console container.
     */
    public function register(Container $container): void
    {
        $container->singleton(ConfigShowRenderer::class, static function (Container $container): ConfigShowRenderer {
            return new ConfigShowRenderer(ConsoleContainerResolver::require($container, ConsoleTableRenderer::class));
        });

        $container->singleton(CacheClearCommand::class, static function (Container $container): CacheClearCommand {
            return new CacheClearCommand(ConsoleContainerResolver::require($container, CacheClearer::class));
        });

        $container->singleton(ConfigCacheCommand::class, static function (Container $container): ConfigCacheCommand {
            return new ConfigCacheCommand(self::configCompiledCache($container));
        });

        $container->singleton(ConfigClearCommand::class, static function (Container $container): ConfigClearCommand {
            $app = ConsoleContainerResolver::require($container, Application::class);

            return new ConfigClearCommand(new ConfigCache($app->basePath()));
        });

        $container->singleton(ConfigShowCommand::class, static function (Container $container): ConfigShowCommand {
            return new ConfigShowCommand(ConsoleContainerResolver::require($container, ConfigShowRenderer::class));
        });

        $container->singleton(ConfigValidateCommand::class, static function (Container $container): ConfigValidateCommand {
            $requirements = $container->has(EnvironmentRequirementRegistry::class)
                ? $container->make(EnvironmentRequirementRegistry::class)
                : new EnvironmentRequirementRegistry();

            if (!$requirements instanceof EnvironmentRequirementRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(EnvironmentRequirementRegistry::class);
            }

            return new ConfigValidateCommand(
                new EnvironmentConfigurationValidator($requirements),
                new EnvironmentValidationRenderer(),
            );
        });
    }

    private static function configCompiledCache(Container $container): ConfigCompiledCache
    {
        try {
            $cache = $container->make(ConfigCompiledCache::class);
        } catch (CannotResolveDependencyException) {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $source = ConsoleContainerResolver::require($container, ConfigSource::class);

            $configCache = new ConfigCache($app->basePath());
            $compiledCache = new ConfigCompiledCache($configCache, new ConfigCacheRebuilder($configCache, $source));
            $registry = self::optionalCompiledCacheRegistry($container);

            if ($registry instanceof CompiledCacheRegistry && $registry->find($compiledCache->name()) === null) {
                $registry->add($compiledCache);
            }

            return $compiledCache;
        }

        if (!$cache instanceof ConfigCompiledCache) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(ConfigCompiledCache::class);
        }

        return $cache;
    }

    private static function optionalCompiledCacheRegistry(Container $container): ?CompiledCacheRegistry
    {
        try {
            $registry = $container->make(CompiledCacheRegistry::class);
        } catch (CannotResolveDependencyException) {
            return null;
        }

        if (!$registry instanceof CompiledCacheRegistry) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(CompiledCacheRegistry::class);
        }

        return $registry;
    }
}
