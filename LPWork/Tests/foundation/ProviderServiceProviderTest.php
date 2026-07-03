<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Exceptions\InvalidServiceProviderException;
use LPWork\Foundation\Providers\ProviderServiceProvider;
use Tests\support\container\DependentService;
use Tests\support\container\SimpleService;

it('registers declared service providers through the container', function (): void {
    $provider = new class extends ProviderServiceProvider {
        /**
         * @return list<class-string>
         */
        protected function serviceProviders(): array
        {
            return [
                Tests\support\foundation\SimpleServiceProvider::class,
            ];
        }
    };

    $container = new Container();
    $provider->register($container);

    expect($container->make(SimpleService::class))->toBeInstanceOf(SimpleService::class);
});

it('registers declared service providers in declaration order', function (): void {
    $provider = new class extends ProviderServiceProvider {
        /**
         * @return list<class-string>
         */
        protected function serviceProviders(): array
        {
            return [
                Tests\support\foundation\SimpleServiceProvider::class,
                Tests\support\foundation\DependentServiceProvider::class,
            ];
        }
    };

    $container = new Container();
    $provider->register($container);

    $service = $container->make(DependentService::class);

    expect($service)->toBeInstanceOf(DependentService::class);

    if ($service instanceof DependentService) {
        expect($service->service)->toBe($container->make(SimpleService::class));
    }
});

it('throws when a declared provider does not implement the service provider contract', function (): void {
    $provider = new class extends ProviderServiceProvider {
        /**
         * @return list<class-string>
         */
        protected function serviceProviders(): array
        {
            return [
                SimpleService::class,
            ];
        }
    };

    expect(fn() => $provider->register(new Container()))
        ->toThrow(
            InvalidServiceProviderException::class,
            sprintf('Service provider must implement %s: %s', ServiceProvider::class, SimpleService::class),
        );
});
