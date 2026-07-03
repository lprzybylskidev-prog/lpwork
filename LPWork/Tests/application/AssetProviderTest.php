<?php

declare(strict_types=1);

use App\Modules\Welcome\Assets\AssetsProvider;
use App\Modules\Welcome\WelcomeServiceProvider;
use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Frontend\Providers\FrontendServiceProvider;

it('defines the welcome module asset provider', function (): void {
    expect(new AssetsProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers the welcome module application asset entry', function (): void {
    $container = new Container();
    new FrontendServiceProvider()->register($container);
    new AssetsProvider()->register($container);

    $entries = $container->make(AssetEntryRegistry::class);

    expect($entries)->toBeInstanceOf(AssetEntryRegistry::class);

    if ($entries instanceof AssetEntryRegistry) {
        expect($entries->get('welcome::app')?->sourcePath())->toBe('App/Modules/Welcome/resources/frontend/app.ts');
    }
});

it('includes asset declarations in the welcome module provider graph', function (): void {
    expect(new WelcomeServiceProvider()->providerClasses())->toContain(AssetsProvider::class);
});
