<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Frontend\ApplicationAssetManifestReader;
use LPWork\Frontend\ApplicationAssetRenderer;
use LPWork\Frontend\ApplicationAssetRenderMode;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Frontend\Providers\ApplicationAssetViewProvider;
use LPWork\View\PhpViewEngineExtensions;

it('exposes the application asset renderer to PHP application views', function (): void {
    $container = new Container();
    $extensions = new PhpViewEngineExtensions();
    $assets = new ApplicationAssetRenderer(
        basePath: \Tests\support\ProjectPaths::root(),
        entries: new AssetEntryRegistry(),
        manifests: new ApplicationAssetManifestReader(\Tests\support\ProjectPaths::root()),
        mode: ApplicationAssetRenderMode::Manifest,
    );

    $container->instance(PhpViewEngineExtensions::class, $extensions);
    $container->instance(ApplicationAssetRenderer::class, $assets);

    new ApplicationAssetViewProvider()->register($container);

    expect($extensions->globals())
        ->toHaveKey('assets')
        ->and($extensions->globals()['assets'])->toBe($assets);
});
