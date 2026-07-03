<?php

declare(strict_types=1);

namespace LPWork\Frontend\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\ServiceProvider;
use LPWork\Frontend\ApplicationAssetRenderer;
use LPWork\View\PhpViewEngineExtensions;

/**
 * Registers application asset view provider services with the framework container.
 */
final class ApplicationAssetViewProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $extensions = $container->make(PhpViewEngineExtensions::class);
        $assets = $container->make(ApplicationAssetRenderer::class);

        if (!$extensions instanceof PhpViewEngineExtensions) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(PhpViewEngineExtensions::class);
        }

        if (!$assets instanceof ApplicationAssetRenderer) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(ApplicationAssetRenderer::class);
        }

        $extensions->global('assets', $assets);
    }
}
