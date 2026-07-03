<?php

declare(strict_types=1);

use App\Modules\Welcome\View\ViewProvider;
use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\View\PhpViewEngineExtensions;

it('defines the welcome module view provider', function (): void {
    expect(new ViewProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers the welcome module view namespace', function (): void {
    $container = new Container();
    $container->instance(PhpViewEngineExtensions::class, new PhpViewEngineExtensions());
    $container->instance(LPWork\View\ViewNamespaceRegistry::class, new LPWork\View\ViewNamespaceRegistry());

    new ViewProvider()->register($container);

    $namespaces = $container->make(LPWork\View\ViewNamespaceRegistry::class);

    expect($container->make(PhpViewEngineExtensions::class))->toBeInstanceOf(PhpViewEngineExtensions::class)
        ->and($namespaces)->toBeInstanceOf(LPWork\View\ViewNamespaceRegistry::class);

    if ($namespaces instanceof LPWork\View\ViewNamespaceRegistry) {
        expect($namespaces->paths('welcome'))->toBe(['App/Modules/Welcome/resources/views']);
    }
});
