<?php

declare(strict_types=1);

namespace LPWork\Frontend\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Foundation\ServiceProvider;
use LPWork\Frontend\ApplicationAssetManifestReader;
use LPWork\Frontend\ApplicationAssetRenderer;
use LPWork\Frontend\ApplicationAssetRenderMode;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Frontend\ViteEntrypointResolver;

/**
 * Registers frontend service provider services with the framework container.
 */
final class FrontendServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(AssetEntryRegistry::class);
        $container->singleton(ViteEntrypointResolver::class);
        $container->singleton(ApplicationAssetManifestReader::class, static function (Container $container): ApplicationAssetManifestReader {
            $app = $container->make(Application::class);
            $files = $container->make(Filesystem::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$files instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            return new ApplicationAssetManifestReader($app->basePath(), $files);
        });
        $container->singleton(ApplicationAssetRenderer::class, static function (Container $container): ApplicationAssetRenderer {
            $app = $container->make(Application::class);
            $files = $container->make(Filesystem::class);
            $entries = $container->make(AssetEntryRegistry::class);
            $manifests = $container->make(ApplicationAssetManifestReader::class);
            $environment = $container->make(RuntimeEnvironment::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$files instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            if (!$entries instanceof AssetEntryRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(AssetEntryRegistry::class);
            }

            if (!$manifests instanceof ApplicationAssetManifestReader) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ApplicationAssetManifestReader::class);
            }

            if (!$environment instanceof RuntimeEnvironment) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(RuntimeEnvironment::class);
            }

            return new ApplicationAssetRenderer(
                basePath: $app->basePath(),
                entries: $entries,
                manifests: $manifests,
                mode: $environment->isProduction() ? ApplicationAssetRenderMode::Manifest : ApplicationAssetRenderMode::DevServer,
                files: $files,
            );
        });
    }
}
