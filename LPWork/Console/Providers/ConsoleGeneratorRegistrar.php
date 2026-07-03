<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Console\Commands\MakeModuleCommand;
use LPWork\Console\FileCreators\FileCreator;
use LPWork\Console\FileCreators\FileCreatorDefinitions;
use LPWork\Console\FileCreators\FileCreatorPathResolver;
use LPWork\Console\FileCreators\FileCreatorTemplateRenderer;
use LPWork\Console\FileCreators\ModuleFileCreatorTargetResolver;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Container\Container;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;

/**
 * Registers console services that generate modules and application files.
 */
final readonly class ConsoleGeneratorRegistrar implements ConsoleServiceRegistrar
{
    /**
     * Adds module and file generator command dependencies to the console container.
     */
    public function register(Container $container): void
    {
        $container->singleton(FileCreatorDefinitions::class);
        $container->singleton(FileCreatorTemplateRenderer::class);

        $container->singleton(ModulePathResolver::class, static function (Container $container): ModulePathResolver {
            return new ModulePathResolver(ConsoleContainerResolver::require($container, Application::class));
        });

        $container->singleton(FileCreatorPathResolver::class, static function (Container $container): FileCreatorPathResolver {
            return new FileCreatorPathResolver(ConsoleContainerResolver::require($container, Application::class));
        });

        $container->singleton(ProviderFileRegistrar::class, static function (Container $container): ProviderFileRegistrar {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $files = ConsoleContainerResolver::require($container, Filesystem::class);

            return new ProviderFileRegistrar($app, $files);
        });

        $container->singleton(ModuleFileCreatorTargetResolver::class, static function (Container $container): ModuleFileCreatorTargetResolver {
            $modules = ConsoleContainerResolver::require($container, ModulePathResolver::class);
            $files = ConsoleContainerResolver::require($container, Filesystem::class);
            $app = ConsoleContainerResolver::require($container, Application::class);

            return new ModuleFileCreatorTargetResolver($modules, $files, $app);
        });

        $container->singleton(ModuleCreator::class, static function (Container $container): ModuleCreator {
            $modules = ConsoleContainerResolver::require($container, ModulePathResolver::class);
            $files = ConsoleContainerResolver::require($container, Filesystem::class);
            $registrar = ConsoleContainerResolver::require($container, ProviderFileRegistrar::class);
            $app = ConsoleContainerResolver::require($container, Application::class);

            return new ModuleCreator($modules, $files, $registrar, $app);
        });

        $container->singleton(MakeModuleCommand::class, static function (Container $container): MakeModuleCommand {
            return new MakeModuleCommand(ConsoleContainerResolver::require($container, ModuleCreator::class));
        });

        $container->singleton(FileCreator::class, static function (Container $container): FileCreator {
            $paths = ConsoleContainerResolver::require($container, FileCreatorPathResolver::class);
            $templates = ConsoleContainerResolver::require($container, FileCreatorTemplateRenderer::class);
            $registrar = ConsoleContainerResolver::require($container, ProviderFileRegistrar::class);
            $files = ConsoleContainerResolver::require($container, Filesystem::class);
            $moduleTargets = ConsoleContainerResolver::require($container, ModuleFileCreatorTargetResolver::class);

            return new FileCreator($paths, $templates, $registrar, $files, $moduleTargets);
        });
    }
}
