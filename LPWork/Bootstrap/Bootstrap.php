<?php

declare(strict_types=1);

namespace LPWork\Bootstrap;

use LPWork\Bootstrap\Exceptions\InvalidApplicationProviderException;
use LPWork\Config\Config;
use LPWork\Config\ConfigCache;
use LPWork\Config\ConfigCacheRebuilder;
use LPWork\Config\ConfigCompiledCache;
use LPWork\Config\EnvironmentConfigurationValidator;
use LPWork\Config\EnvironmentRequirementRegistry;
use LPWork\Config\EnvironmentValidationRenderer;
use LPWork\Config\Providers\ConfigsProvider;
use LPWork\Console\CommandHelpRenderer;
use LPWork\Console\CommandListRenderer;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Commands\ConfigCacheCommand;
use LPWork\Console\Commands\ConfigClearCommand;
use LPWork\Console\Commands\ConfigValidateCommand;
use LPWork\Console\ConsoleBootstrapNotice;
use LPWork\Environment\Environment;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Renderers\CliExceptionRenderer;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\FrameworkModuleCatalog;
use LPWork\Foundation\FrameworkModuleRegistry;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Routing\RouteCache;
use LPWork\Security\ApplicationKey;
use Throwable;

/**
 * Represents the bootstrap framework component.
 */
final class Bootstrap
{
    private const APPLICATION_NAMESPACE = 'App';

    private const CONFIG_PROVIDER_SUFFIX = 'Shared\\Configs\\ConfigsProvider';

    private const SERVICE_PROVIDER_SUFFIX = 'AppServiceProvider';

    /**
     * Performs the init operation.
     */
    public static function init(string $basePath): Application
    {
        return self::create($basePath, validateApplicationKey: true, clearRouteCacheBeforeLoad: false);
    }

    /**
     * @param array<int, string> $argv
     */
    public static function initForConsole(string $basePath, array $argv): Application
    {
        if (self::isConfigValidationCommand($argv)) {
            return self::createConfigValidationConsole($basePath);
        }

        if (self::isConfigCacheMaintenanceCommand($argv)) {
            return self::createConfigCacheMaintenanceConsole($basePath);
        }

        try {
            return self::create(
                $basePath,
                validateApplicationKey: !self::isKeyGenerationCommand($argv) && !self::isCompletionGenerationCommand($argv),
                clearRouteCacheBeforeLoad: self::isRouteCacheCommand($argv),
            );
        } catch (Throwable $throwable) {
            if (!self::canRecoverFromConfigCacheFailure($basePath)) {
                throw $throwable;
            }

            return self::createConfigCacheMaintenanceConsole(
                $basePath,
                new ConsoleBootstrapNotice('The compiled configuration cache is invalid and must be cleared or rebuilt.'),
                initializeEnvironment: false,
            );
        }
    }

    private static function create(string $basePath, bool $validateApplicationKey, bool $clearRouteCacheBeforeLoad): Application
    {
        Environment::init($basePath . '/.env');
        $app = new Application($basePath);
        $appServiceProvider = self::applicationServiceProvider();

        $app->register(new FoundationServiceProvider($app));
        $catalog = new FrameworkModuleCatalog();
        $app->container()->instance(FrameworkModuleCatalog::class, $catalog);
        $app->container()->instance(FrameworkModuleRegistry::class, new FrameworkModuleRegistry(array_map(
            static fn(\LPWork\Foundation\FrameworkModuleDefinition $module): string => $module->key(),
            $catalog->all(),
        )));
        $app->register(self::applicationConfigProvider($app));
        if ($clearRouteCacheBeforeLoad) {
            new RouteCache($basePath)->clear();
        }
        if ($validateApplicationKey) {
            self::validateApplicationKey();
        }

        self::configurePhpErrorSettings();

        new FrameworkServiceProviderRegistrar()->register($app, $appServiceProvider);

        return $app;
    }

    /**
     * @param array<int, string> $argv
     */
    private static function isKeyGenerationCommand(array $argv): bool
    {
        return ($argv[1] ?? null) === 'key:generate';
    }

    /**
     * @param array<int, string> $argv
     */
    private static function isCompletionGenerationCommand(array $argv): bool
    {
        return ($argv[1] ?? null) === 'completion:generate';
    }

    /**
     * @param array<int, string> $argv
     */
    private static function isConfigCacheMaintenanceCommand(array $argv): bool
    {
        $command = $argv[1] ?? null;

        return $command === 'config:cache' || $command === 'config:clear';
    }

    /**
     * @param array<int, string> $argv
     */
    private static function isConfigValidationCommand(array $argv): bool
    {
        return ($argv[1] ?? null) === 'config:validate';
    }

    /**
     * @param array<int, string> $argv
     */
    private static function isRouteCacheCommand(array $argv): bool
    {
        return ($argv[1] ?? null) === 'route:cache' || ($argv[1] ?? null) === 'cache:rebuild';
    }

    private static function canRecoverFromConfigCacheFailure(string $basePath): bool
    {
        return new ConfigCache($basePath)->exists();
    }

    private static function createConfigCacheMaintenanceConsole(
        string $basePath,
        ?ConsoleBootstrapNotice $notice = null,
        bool $initializeEnvironment = true,
    ): Application {
        Config::reset();

        if ($initializeEnvironment) {
            Environment::init($basePath . '/.env');
        }

        $app = new Application($basePath);
        $app->register(new FoundationServiceProvider($app));

        $cache = new ConfigCache($basePath);
        $configProvider = self::applicationConfigProvider($app);
        $source = $configProvider->source($app->container());
        $registry = new CommandRegistry();

        Config::initSource($source);

        $registry->add(new ConfigCacheCommand(new ConfigCompiledCache(
            $cache,
            new ConfigCacheRebuilder(
                $cache,
                $source,
            ),
        )));
        $registry->add(new ConfigClearCommand($cache));

        $app->container()->instance(CommandRegistry::class, $registry);
        $app->container()->singleton(CommandHelpRenderer::class);
        $app->container()->instance(CommandListRenderer::class, new CommandListRenderer($notice));

        if ($notice !== null) {
            $app->container()->instance(ConsoleBootstrapNotice::class, $notice);
        }

        return $app;
    }

    private static function createConfigValidationConsole(string $basePath): Application
    {
        Config::reset();
        Environment::init($basePath . '/.env');

        $app = new Application($basePath);
        $app->register(new FoundationServiceProvider($app));

        $configProvider = self::applicationConfigProvider($app);
        $configProvider->source($app->container());

        $requirements = $app->container()->make(EnvironmentRequirementRegistry::class);

        if (!$requirements instanceof EnvironmentRequirementRegistry) {
            $requirements = new EnvironmentRequirementRegistry();
        }

        $registry = new CommandRegistry();
        $registry->add(new ConfigValidateCommand(
            new EnvironmentConfigurationValidator($requirements),
            new EnvironmentValidationRenderer(),
        ));

        $app->container()->instance(ExceptionReporter::class, new /**
         * Represents the exception reporter framework component.
         */
 class implements ExceptionReporter {
     /**
      * Performs the report operation.
      */
     public function report(Throwable $throwable): void {}
 });
        $app->container()->instance(CliExceptionRenderer::class, new CliExceptionRenderer());
        $app->container()->instance(CommandRegistry::class, $registry);
        $app->container()->singleton(CommandHelpRenderer::class);
        $app->container()->instance(CommandListRenderer::class, new CommandListRenderer());

        return $app;
    }

    private static function configurePhpErrorSettings(): void
    {
        error_reporting(Config::getInt('error.reporting'));

        ini_set('display_errors', Config::getInt('error.display'));
        ini_set('display_startup_errors', Config::getInt('error.display_startup'));

        ini_set('log_errors', Config::getInt('error.log'));
    }

    private static function validateApplicationKey(): void
    {
        ApplicationKey::fromString(Config::getString('security.app_key'));
    }

    private static function applicationConfigProvider(Application $app): ConfigsProvider
    {
        $class = self::applicationProviderClass(self::CONFIG_PROVIDER_SUFFIX);

        if (!class_exists($class)) {
            throw InvalidApplicationProviderException::missing($class);
        }

        if (!is_subclass_of($class, ConfigsProvider::class)) {
            throw InvalidApplicationProviderException::invalid($class, ConfigsProvider::class);
        }

        return new $class($app);
    }

    private static function applicationServiceProvider(): ServiceProvider
    {
        $class = self::applicationProviderClass(self::SERVICE_PROVIDER_SUFFIX);

        if (!class_exists($class)) {
            throw InvalidApplicationProviderException::missing($class);
        }

        if (!is_subclass_of($class, ServiceProvider::class)) {
            throw InvalidApplicationProviderException::invalid($class, ServiceProvider::class);
        }

        return new $class();
    }

    private static function applicationProviderClass(string $suffix): string
    {
        return self::APPLICATION_NAMESPACE . '\\' . $suffix;
    }
}
