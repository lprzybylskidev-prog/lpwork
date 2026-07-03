<?php

declare(strict_types=1);

namespace LPWork\Foundation\Providers;

use LPWork\Config\Config;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Container\Container;
use LPWork\Foundation\Application;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Foundation\RuntimeEnvironmentFactory;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers foundation service provider services with the framework container.
 */
final class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Creates a new FoundationServiceProvider instance.
     */
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->instance(Container::class, $container);
        $container->instance(Application::class, $this->app);
        $container->singleton(CompiledCacheRegistry::class);
        $container->singleton(FrameworkMetadata::class);
        $container->singleton(
            RuntimeEnvironment::class,
            static fn(): RuntimeEnvironment => new RuntimeEnvironmentFactory()->create(
                self::environmentName(),
                self::productionEnvironments(),
            ),
        );
    }

    private static function environmentName(): string
    {
        try {
            return Config::getString('app.env');
        } catch (MissingVariableException) {
            return 'development';
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function productionEnvironments(): array
    {
        try {
            return Config::getArray('security.production_environments');
        } catch (MissingVariableException) {
            return ['production'];
        }
    }
}
