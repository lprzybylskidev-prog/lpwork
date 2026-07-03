<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use Tests\support\container\SimpleService;

it('builds paths relative to the application base path', function (): void {
    $app = new Application('/app');

    expect($app->basePath())->toBe('/app')
        ->and($app->basePath('storage/logs'))->toBe('/app/storage/logs')
        ->and($app->basePath('/storage/logs'))->toBe('/app/storage/logs')
        ->and($app->basePath(''))->toBe('/app');
});

it('owns a container instance', function (): void {
    $app = new Application('/app');

    expect($app->container())->toBeInstanceOf(Container::class);
});

it('accepts an existing container instance', function (): void {
    $container = new Container();
    $app = new Application('/app', $container);

    expect($app->container())->toBe($container);
});

it('registers service providers into its container', function (): void {
    $app = new Application('/app');

    $app->register(new class implements ServiceProvider {
        public function register(Container $container): void
        {
            $container->instance(SimpleService::class, new SimpleService());
        }
    });

    expect($app->container()->make(SimpleService::class))->toBeInstanceOf(SimpleService::class);
});
