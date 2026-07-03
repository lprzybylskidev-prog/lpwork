<?php

declare(strict_types=1);

namespace LPWork\Foundation\Providers;

use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider as ServiceProviderContract;
use LPWork\Foundation\Exceptions\InvalidServiceProviderException;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers provider service provider services with the framework container.
 */
abstract class ProviderServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $this->registerServiceProviders($container, $this->serviceProviders());
    }

    /**
     * @param list<class-string> $providers
     */
    protected function registerServiceProviders(Container $container, array $providers): void
    {
        foreach ($providers as $provider) {
            $resolved = $container->make($provider);

            if (!$resolved instanceof ServiceProviderContract) {
                throw InvalidServiceProviderException::doesNotImplementContract($provider);
            }

            $resolved->register($container);
        }
    }

    /**
     * @return list<class-string>
     */
    final public function providerClasses(): array
    {
        return $this->serviceProviders();
    }

    /**
     * @return list<class-string>
     */
    abstract protected function serviceProviders(): array;
}
