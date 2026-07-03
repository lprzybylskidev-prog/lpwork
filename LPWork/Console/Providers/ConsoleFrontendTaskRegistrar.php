<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Console\Commands\FrontendEntrypointsCommand;
use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\ProjectTasks\ProjectFileFinder;
use LPWork\Console\ProjectTasks\ProjectTaskRunner;
use LPWork\Container\Container;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Frontend\BrowserTaskRunner;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Frontend\FrontendProcessFactory;
use LPWork\Frontend\FrontendTaskRunner;
use LPWork\Frontend\ViteEntrypointResolver;

/**
 * Registers console services that run frontend, browser, and project-level development tasks.
 */
final readonly class ConsoleFrontendTaskRegistrar implements ConsoleServiceRegistrar
{
    /**
     * Adds frontend, browser, and project-task command dependencies to the console container.
     */
    public function register(Container $container): void
    {
        $container->singleton(FrontendPackageManagerDetector::class, static function (Container $container): FrontendPackageManagerDetector {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $files = ConsoleContainerResolver::require($container, Filesystem::class);

            return new FrontendPackageManagerDetector($app->basePath(), $files);
        });

        $container->singleton(FrontendProcessFactory::class, static function (Container $container): FrontendProcessFactory {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $packageManagers = ConsoleContainerResolver::require($container, FrontendPackageManagerDetector::class);

            return new FrontendProcessFactory($app->basePath(), $packageManagers);
        });

        $container->singleton(FrontendTaskRunner::class, static function (Container $container): FrontendTaskRunner {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $frontendProcesses = ConsoleContainerResolver::require($container, FrontendProcessFactory::class);
            $processes = ConsoleContainerResolver::require($container, ProcessRunner::class);
            $files = ConsoleContainerResolver::require($container, Filesystem::class);

            return new FrontendTaskRunner($app->basePath(), $frontendProcesses, $processes, $files);
        });

        $container->singleton(BrowserTaskRunner::class, static function (Container $container): BrowserTaskRunner {
            $frontendProcesses = ConsoleContainerResolver::require($container, FrontendProcessFactory::class);
            $processes = ConsoleContainerResolver::require($container, ProcessRunner::class);

            return new BrowserTaskRunner($frontendProcesses, $processes);
        });

        $container->singleton(FrontendEntrypointsCommand::class, static function (Container $container): FrontendEntrypointsCommand {
            return new FrontendEntrypointsCommand(ConsoleContainerResolver::require($container, ViteEntrypointResolver::class));
        });

        $container->singleton(ProjectFileFinder::class, static function (Container $container): ProjectFileFinder {
            $app = ConsoleContainerResolver::require($container, Application::class);

            return new ProjectFileFinder($app->basePath());
        });

        $container->singleton(ProjectTaskRunner::class, static function (Container $container): ProjectTaskRunner {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $files = ConsoleContainerResolver::require($container, ProjectFileFinder::class);
            $processes = ConsoleContainerResolver::require($container, ProcessRunner::class);
            $frontend = ConsoleContainerResolver::require($container, FrontendTaskRunner::class);
            $browser = ConsoleContainerResolver::require($container, BrowserTaskRunner::class);

            return new ProjectTaskRunner($app->basePath(), $files, $processes, $frontend, $browser);
        });
    }
}
