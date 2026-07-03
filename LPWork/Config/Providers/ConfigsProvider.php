<?php

declare(strict_types=1);

namespace LPWork\Config\Providers;

use LPWork\Config\Config;
use LPWork\Config\ConfigCache;
use LPWork\Config\ConfigCacheRebuilder;
use LPWork\Config\ConfigCompiledCache;
use LPWork\Config\ConfigSourceDefinitions;
use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigDefinitionProvider;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Config\EnvironmentRequirementRegistry;
use LPWork\Container\Container;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers configs provider services with the framework container.
 */
abstract class ConfigsProvider extends ServiceProvider
{
    /**
     * Creates a new ConfigsProvider instance.
     */
    public function __construct(
        protected readonly Application $app,
    ) {}

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $source = $this->source($container);
        $container->instance(ConfigSource::class, $source);

        $cache = new ConfigCache($this->app->basePath());
        $container->instance(ConfigCache::class, $cache);
        $container->instance(ConfigCacheRebuilder::class, new ConfigCacheRebuilder($cache, $source));
        $container->singleton(ConfigCompiledCache::class);
        $this->registerCompiledCaches($container, [ConfigCompiledCache::class]);

        if ($cache->exists()) {
            $cache->load();

            return;
        }

        Config::initSource($source);
    }

    /**
     * Performs the source operation.
     */
    final public function source(Container $container): ConfigSource
    {
        $definitions = $this->definitions($container);

        $container->instance(EnvironmentRequirementRegistry::class, EnvironmentRequirementRegistry::fromDefinitions($definitions));

        return new ConfigSourceDefinitions($definitions);
    }

    /**
     * @return list<class-string<ConfigDefinition>>
     */
    abstract protected function configDefinitions(): array;

    /**
     * @return list<class-string<ConfigDefinitionProvider>>
     */
    protected function configDefinitionProviders(): array
    {
        return [];
    }

    /**
     * @return list<ConfigDefinition>
     */
    private function definitions(Container $container): array
    {
        $definitions = [];

        foreach ($this->configDefinitions() as $definition) {
            $resolved = $container->make($definition);

            if ($resolved instanceof ConfigDefinition) {
                $definitions[] = $resolved;
            }
        }

        foreach ($this->configDefinitionProviders() as $provider) {
            $resolved = $container->make($provider);

            if (!$resolved instanceof ConfigDefinitionProvider) {
                continue;
            }

            foreach ($resolved->configDefinitions() as $definition) {
                $resolvedDefinition = $container->make($definition);

                if ($resolvedDefinition instanceof ConfigDefinition) {
                    $definitions[] = $resolvedDefinition;
                }
            }
        }

        return $definitions;
    }
}
