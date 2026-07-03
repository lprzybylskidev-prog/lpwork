<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Providers;

use LPWork\Config\Config;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Kernels\Http\ControllerDispatcher;
use LPWork\Maintenance\Commands\MaintenanceDownCommand;
use LPWork\Maintenance\Commands\MaintenanceStatusCommand;
use LPWork\Maintenance\Commands\MaintenanceUpCommand;
use LPWork\Maintenance\FileMaintenanceStore;
use LPWork\Maintenance\MaintenanceMode;
use LPWork\Maintenance\MaintenancePageRenderer;
use LPWork\Maintenance\MaintenanceStore;
use LPWork\Routing\Router;

/**
 * Registers maintenance service provider services with the framework container.
 */
final class MaintenanceServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(MaintenanceStore::class, static function (Container $container): MaintenanceStore {
            $app = $container->make(Application::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            return new FileMaintenanceStore(
                new Filesystem(),
                $app->basePath(self::maintenanceFile()),
            );
        });
        $container->singleton(MaintenanceMode::class);
        $container->singleton(MaintenancePageRenderer::class, static function (Container $container): MaintenancePageRenderer {
            $router = null;
            $dispatcher = null;

            if (self::maintenanceRoute() !== null) {
                $resolvedRouter = $container->make(Router::class);
                $resolvedDispatcher = $container->make(ControllerDispatcher::class);

                if (!$resolvedRouter instanceof Router) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(Router::class);
                }

                if (!$resolvedDispatcher instanceof ControllerDispatcher) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(ControllerDispatcher::class);
                }

                $router = $resolvedRouter;
                $dispatcher = $resolvedDispatcher;
            }

            return new MaintenancePageRenderer(
                router: $router,
                dispatcher: $dispatcher,
                route: self::maintenanceRoute(),
            );
        });
        $container->singleton(MaintenanceDownCommand::class);
        $container->singleton(MaintenanceUpCommand::class);
        $container->singleton(MaintenanceStatusCommand::class);

        $this->registerCommands($container, [
            MaintenanceDownCommand::class,
            MaintenanceUpCommand::class,
            MaintenanceStatusCommand::class,
        ]);
    }

    private static function maintenanceFile(): string
    {
        try {
            return Config::getString('maintenance.file');
        } catch (MissingVariableException) {
            return 'storage/framework/maintenance.json';
        }
    }

    private static function maintenanceRoute(): ?string
    {
        $route = Config::get('maintenance.route', null);

        return is_string($route) && $route !== '' ? $route : null;
    }
}
