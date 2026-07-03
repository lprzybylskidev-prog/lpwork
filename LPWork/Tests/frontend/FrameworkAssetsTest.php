<?php

declare(strict_types=1);

use LPWork\Frontend\FrameworkAssets;

it('builds cache-busted public framework asset URLs', function (): void {
    $logoVersion = FrameworkAssets::assetVersion('/assets/lpwork-logo.svg');
    $faviconVersion = FrameworkAssets::assetVersion('/favicon.svg');

    expect($logoVersion)->toMatch('/^[a-f0-9]{12}$/')
        ->and($faviconVersion)->toMatch('/^[a-f0-9]{12}$/')
        ->and(FrameworkAssets::logoUrl())->toBe('/assets/lpwork-logo.svg?v=' . $logoVersion)
        ->and(FrameworkAssets::faviconUrl())->toBe('/favicon.svg?v=' . $faviconVersion)
        ->and(FrameworkAssets::faviconLink())->toBe('<link rel="icon" href="/favicon.svg?v=' . $faviconVersion . '" type="image/svg+xml">');
});

it('uses public logo URLs for browser brands and data URIs for inline-safe brands', function (): void {
    $browserBrand = FrameworkAssets::brand('Diagnostics');
    $inlineBrand = FrameworkAssets::brand('Mail', inlineLogo: true);

    expect($browserBrand)->toContain('src="/assets/lpwork-logo.svg?v=');
    expect($browserBrand)->not->toContain('data:image/svg+xml;base64,');
    expect($inlineBrand)->toContain('src="data:image/svg+xml;base64,');
});

it('keeps public logo and favicon assets aligned with the framework logo source', function (): void {
    $root = \Tests\support\ProjectPaths::root();
    $logo = file_get_contents($root . '/public/assets/lpwork-logo.svg');
    $favicon = file_get_contents($root . '/public/favicon.svg');

    expect($logo)->toBe(FrameworkAssets::logoSvg())
        ->and($favicon)->toBe(FrameworkAssets::logoSvg());
});

it('wraps the shared stylesheet in a reusable style element', function (): void {
    expect(FrameworkAssets::stylesheetElement())
        ->toStartWith('<style>')
        ->toContain('--lp-ui-bg: #090b0f')
        ->toEndWith('</style>');
});

it('loads the shared stylesheet from a framework resource file', function (): void {
    $stylesheet = file_get_contents(FrameworkAssets::STYLESHEET_RESOURCE);

    expect($stylesheet)->toBeString()
        ->and(FrameworkAssets::stylesheet())->toBe($stylesheet);
});
