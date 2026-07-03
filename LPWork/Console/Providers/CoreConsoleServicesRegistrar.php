<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Config\Config;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Console\CommandDiscovery;
use LPWork\Console\CommandHelpRenderer;
use LPWork\Console\CommandListRenderer;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Commands\AboutCommand;
use LPWork\Console\Commands\AboutRuntimeSnapshotFactory;
use LPWork\Console\Commands\GenerateApplicationKeyCommand;
use LPWork\Console\Commands\RouteListCommand;
use LPWork\Console\Completion\CompletionInstaller;
use LPWork\Console\Completion\CompletionScriptGenerator;
use LPWork\Console\ConsoleMiddlewareResolver;
use LPWork\Console\ConsoleMiddlewareStack;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\GlobalHelpRenderer;
use LPWork\Console\NativeProcessRunner;
use LPWork\Console\Output;
use LPWork\Console\Questioner;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\FrameworkModuleCatalog;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Routing\RouteCollection;
use LPWork\Routing\RouteListRenderer;
use LPWork\Security\ApplicationKeyGenerator;
use LPWork\Security\EnvironmentApplicationKeyStore;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\TranslationNamespaceRegistry;
use LPWork\Translation\Translator;

/**
 * Registers the shared console runtime, built-in core commands, and command middleware services.
 */
final readonly class CoreConsoleServicesRegistrar implements ConsoleServiceRegistrar
{
    /**
     * Adds shared console runtime services, core commands, renderers, discovery, and middleware.
     */
    public function register(Container $container): void
    {
        $container->singleton(Output::class, static fn(): Output => Output::terminal());
        $container->singleton(ProcessRunner::class, NativeProcessRunner::class);
        $container->singleton(Questioner::class, static function (Container $container): Questioner {
            return Questioner::terminal(ConsoleContainerResolver::require($container, Output::class));
        });

        $container->singleton(GenerateApplicationKeyCommand::class, static function (Container $container): GenerateApplicationKeyCommand {
            $app = ConsoleContainerResolver::require($container, Application::class);

            return new GenerateApplicationKeyCommand(
                new EnvironmentApplicationKeyStore($app->basePath('.env')),
                new ApplicationKeyGenerator(),
            );
        });

        $container->singleton(AboutCommand::class, static function (Container $container): AboutCommand {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $caches = ConsoleContainerResolver::require($container, CompiledCacheRegistry::class);
            $environment = ConsoleContainerResolver::require($container, RuntimeEnvironment::class);
            $metadata = ConsoleContainerResolver::require($container, FrameworkMetadata::class);
            $modules = ConsoleContainerResolver::require($container, FrameworkModuleCatalog::class);
            $translator = self::translator($container, $app);

            return new AboutCommand(new AboutRuntimeSnapshotFactory()->create($app, $environment, $metadata, $translator), $modules, $caches, $translator);
        });

        $container->singleton(ConsoleTableRenderer::class);

        $container->singleton(RouteListRenderer::class, static function (Container $container): RouteListRenderer {
            return new RouteListRenderer(ConsoleContainerResolver::require($container, ConsoleTableRenderer::class));
        });

        $container->singleton(RouteListCommand::class, static function (Container $container): RouteListCommand {
            $routes = ConsoleContainerResolver::require($container, RouteCollection::class);
            $renderer = ConsoleContainerResolver::require($container, RouteListRenderer::class);

            return new RouteListCommand($routes, $renderer);
        });

        $container->singleton(CompletionScriptGenerator::class, static fn(): CompletionScriptGenerator => CompletionScriptGenerator::default());
        $container->singleton(CompletionInstaller::class, static fn(): CompletionInstaller => new CompletionInstaller());
        $container->singleton(CommandRegistry::class, static fn(Container $container): CommandRegistry => new ConsoleCommandRegistryFactory()->create($container));
        $container->singleton(ConsoleMiddlewareStack::class);
        $container->singleton(ConsoleMiddlewareResolver::class, static function (Container $container): ConsoleMiddlewareResolver {
            $app = ConsoleContainerResolver::require($container, Application::class);
            $stack = ConsoleContainerResolver::require($container, ConsoleMiddlewareStack::class);

            return new ConsoleMiddlewareResolver(
                app: $app,
                globalMiddleware: $stack,
                productionEnvironment: self::productionEnvironment($container),
            );
        });

        $container->singleton(CommandListRenderer::class);
        $container->singleton(CommandHelpRenderer::class);
        $container->singleton(GlobalHelpRenderer::class);
        $container->singleton(CommandDiscovery::class, static fn(Container $container): CommandDiscovery => new CommandDiscovery($container));
    }

    private static function productionEnvironment(Container $container): bool
    {
        try {
            $environment = $container->make(RuntimeEnvironment::class);

            return $environment instanceof RuntimeEnvironment && $environment->isProduction();
        } catch (MissingVariableException) {
            return false;
        }
    }

    private static function translator(Container $container, Application $app): Translator
    {
        try {
            $translator = $container->make(Translator::class);
        } catch (CannotResolveDependencyException) {
            return new Translator(
                loader: new JsonTranslationLoader(
                    path: $app->basePath('App/Shared/lang'),
                    namespaces: self::frameworkTranslationNamespace(),
                ),
                locale: self::configString('app.lang', 'en_US'),
                fallbackLocale: 'en_US',
            );
        }

        if (!$translator instanceof Translator) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(Translator::class);
        }

        return $translator;
    }

    private static function frameworkTranslationNamespace(): TranslationNamespaceRegistry
    {
        $namespaces = new TranslationNamespaceRegistry();
        $namespaces->add('lpwork', dirname(__DIR__, 2) . '/Foundation/lang');

        return $namespaces;
    }

    private static function configString(string $key, string $fallback): string
    {
        try {
            return Config::getString($key);
        } catch (MissingVariableException) {
            return $fallback;
        }
    }
}
