<?php

declare(strict_types=1);

namespace LPWork\Health\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Foundation\ServiceProvider;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Health\Checks\CompiledCachesHealthCheck;
use LPWork\Health\Checks\DevelopmentPhpExtensionsHealthCheck;
use LPWork\Health\Checks\DevelopmentToolsHealthCheck;
use LPWork\Health\Checks\FrameworkModulesHealthCheck;
use LPWork\Health\Checks\FrontendBuildOutputHealthCheck;
use LPWork\Health\Checks\FrontendHealthConfiguration;
use LPWork\Health\Checks\FrontendQualityHealthCheck;
use LPWork\Health\Checks\FrontendRuntimeHealthCheck;
use LPWork\Health\Checks\FrontendTestingHealthCheck;
use LPWork\Health\Checks\PhpRuntimeHealthCheck;
use LPWork\Health\Checks\RuntimeDirectoriesHealthCheck;
use LPWork\Health\Commands\HealthCheckCommand;
use LPWork\Health\Contracts\PhpRuntimeInspector;
use LPWork\Health\HealthCheckRegistry;
use LPWork\Health\HealthRunner;
use LPWork\Health\NativePhpRuntimeInspector;

/**
 * Registers health service provider services with the framework container.
 */
final class HealthServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(PhpRuntimeInspector::class, NativePhpRuntimeInspector::class);
        $container->singleton(HealthCheckRegistry::class);
        $container->singleton(HealthRunner::class);
        $container->singleton(PhpRuntimeHealthCheck::class);
        $container->singleton(FrameworkModulesHealthCheck::class);
        $container->singleton(RuntimeDirectoriesHealthCheck::class, static function (Container $container): RuntimeDirectoriesHealthCheck {
            $app = $container->make(Application::class);
            $files = $container->make(Filesystem::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$files instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            return new RuntimeDirectoriesHealthCheck($app->basePath(), $files);
        });
        $container->singleton(CompiledCachesHealthCheck::class, static function (Container $container): CompiledCachesHealthCheck {
            $caches = $container->make(CompiledCacheRegistry::class);
            $files = $container->make(Filesystem::class);

            if (!$caches instanceof CompiledCacheRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CompiledCacheRegistry::class);
            }

            if (!$files instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            return new CompiledCachesHealthCheck($caches, $files);
        });
        $container->singleton(DevelopmentToolsHealthCheck::class);
        $container->singleton(DevelopmentPhpExtensionsHealthCheck::class);
        $container->singleton(FrontendPackageManagerDetector::class, static function (Container $container): FrontendPackageManagerDetector {
            $app = $container->make(Application::class);
            $files = $container->make(Filesystem::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$files instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            return new FrontendPackageManagerDetector($app->basePath(), $files);
        });
        $container->singleton(FrontendHealthConfiguration::class, static function (Container $container): FrontendHealthConfiguration {
            $app = $container->make(Application::class);
            $files = $container->make(Filesystem::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$files instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            return new FrontendHealthConfiguration($app->basePath(), $files);
        });
        $container->singleton(FrontendRuntimeHealthCheck::class, static function (Container $container): FrontendRuntimeHealthCheck {
            $frontend = $container->make(FrontendHealthConfiguration::class);
            $packageManagers = $container->make(FrontendPackageManagerDetector::class);

            if (!$frontend instanceof FrontendHealthConfiguration) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(FrontendHealthConfiguration::class);
            }

            if (!$packageManagers instanceof FrontendPackageManagerDetector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(FrontendPackageManagerDetector::class);
            }

            return new FrontendRuntimeHealthCheck($frontend, $packageManagers);
        });
        $container->singleton(FrontendQualityHealthCheck::class);
        $container->singleton(FrontendTestingHealthCheck::class);
        $container->singleton(FrontendBuildOutputHealthCheck::class, static function (Container $container): FrontendBuildOutputHealthCheck {
            $frontend = $container->make(FrontendHealthConfiguration::class);
            $environment = $container->make(RuntimeEnvironment::class);

            if (!$frontend instanceof FrontendHealthConfiguration) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(FrontendHealthConfiguration::class);
            }

            if (!$environment instanceof RuntimeEnvironment) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(RuntimeEnvironment::class);
            }

            return new FrontendBuildOutputHealthCheck($frontend, $environment);
        });
        $container->singleton(HealthCheckCommand::class);

        $this->registerHealthCheck($container, PhpRuntimeHealthCheck::class);
        $this->registerHealthCheck($container, FrameworkModulesHealthCheck::class);
        $this->registerHealthCheck($container, RuntimeDirectoriesHealthCheck::class);
        $this->registerHealthCheck($container, CompiledCachesHealthCheck::class);
        $this->registerHealthCheck($container, DevelopmentToolsHealthCheck::class);
        $this->registerHealthCheck($container, DevelopmentPhpExtensionsHealthCheck::class);
        $this->registerHealthCheck($container, FrontendRuntimeHealthCheck::class);
        $this->registerHealthCheck($container, FrontendQualityHealthCheck::class);
        $this->registerHealthCheck($container, FrontendTestingHealthCheck::class);
        $this->registerHealthCheck($container, FrontendBuildOutputHealthCheck::class);
    }
}
