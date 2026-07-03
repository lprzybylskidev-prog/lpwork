<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Emitters\HttpEmitter;
use LPWork\Emitters\Providers\EmitterServiceProvider;
use LPWork\Foundation\Contracts\ServiceProvider;

it('defines the emitter service provider', function (): void {
    $provider = new EmitterServiceProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('registers framework emitters', function (): void {
    $container = new Container();
    $provider = new EmitterServiceProvider();

    $provider->register($container);

    expect($container->make(ConsoleEmitter::class))->toBeInstanceOf(ConsoleEmitter::class)
        ->and($container->make(HttpEmitter::class))->toBeInstanceOf(HttpEmitter::class);
});
