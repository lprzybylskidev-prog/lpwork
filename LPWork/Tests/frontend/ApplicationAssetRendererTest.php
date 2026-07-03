<?php

declare(strict_types=1);

use LPWork\Filesystem\Filesystem;
use LPWork\Frontend\ApplicationAssetManifestReader;
use LPWork\Frontend\ApplicationAssetRenderer;
use LPWork\Frontend\ApplicationAssetRenderMode;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Frontend\Contracts\ApplicationAssetDevServerProbe;
use LPWork\Frontend\Exceptions\ApplicationAssetBuiltFileMissingException;
use LPWork\Frontend\Exceptions\ApplicationAssetDevServerUnavailableException;
use LPWork\Frontend\Exceptions\ApplicationAssetEntryNotFoundException;
use LPWork\Frontend\Exceptions\ApplicationAssetManifestEntryNotFoundException;
use LPWork\Frontend\Exceptions\ApplicationAssetManifestMissingException;
use LPWork\Frontend\Exceptions\ApplicationAssetSourceFileMissingException;

afterEach(function (): void {
    new Filesystem()->clearDirectory(\Tests\support\ProjectPaths::root() . '/storage/testing/application-assets');
});

it('renders Vite dev server tags for declared entries', function (): void {
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: \Tests\support\ProjectPaths::root(),
        entries: $entries,
        manifests: new ApplicationAssetManifestReader(\Tests\support\ProjectPaths::root()),
        mode: ApplicationAssetRenderMode::DevServer,
        devServers: new class implements ApplicationAssetDevServerProbe {
            public function reachable(string $url): bool
            {
                return true;
            }
        },
    );

    expect($renderer->entry('welcome::app'))->toBe(implode("\n", [
        '<script type="module" src="http://localhost:5173/@vite/client"></script>',
        '<script type="module" src="http://localhost:5173/App/Modules/Welcome/resources/frontend/app.ts"></script>',
    ]));
});

it('renders manifest tags for declared entries', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/application-assets';
    $files = new Filesystem();
    $files->write($basePath . '/App/Modules/Welcome/resources/frontend/app.ts', "import './app.css';\n");
    $files->write($basePath . '/public/build/manifest.json', json_encode([
        'App/Modules/Welcome/resources/frontend/app.ts' => [
            'file' => 'assets/welcome/app-DjVb6W5s.js',
            'css' => ['assets/app-TDyv7-ox.css'],
        ],
    ], JSON_THROW_ON_ERROR));
    $files->write($basePath . '/public/build/assets/welcome/app-DjVb6W5s.js', 'console.log("welcome");');
    $files->write($basePath . '/public/build/assets/app-TDyv7-ox.css', 'body {}');
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: $basePath,
        entries: $entries,
        manifests: new ApplicationAssetManifestReader($basePath, $files),
        mode: ApplicationAssetRenderMode::Manifest,
        files: $files,
    );

    expect($renderer->entry('welcome::app'))->toBe(implode("\n", [
        '<link rel="stylesheet" href="/build/assets/app-TDyv7-ox.css">',
        '<script type="module" src="/build/assets/welcome/app-DjVb6W5s.js"></script>',
    ]));
});

it('reports missing application asset entries', function (): void {
    $renderer = new ApplicationAssetRenderer(
        basePath: \Tests\support\ProjectPaths::root(),
        entries: new AssetEntryRegistry(),
        manifests: new ApplicationAssetManifestReader(\Tests\support\ProjectPaths::root()),
        mode: ApplicationAssetRenderMode::DevServer,
    );

    expect(fn() => $renderer->entry('missing::app'))
        ->toThrow(ApplicationAssetEntryNotFoundException::class, 'Add it through an application asset entry provider');
});

it('reports missing manifest files', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/application-assets';
    $files = new Filesystem();
    $files->write($basePath . '/App/Modules/Welcome/resources/frontend/app.ts', "import './app.css';\n");
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: $basePath,
        entries: $entries,
        manifests: new ApplicationAssetManifestReader($basePath, $files),
        mode: ApplicationAssetRenderMode::Manifest,
        files: $files,
    );

    expect(fn() => $renderer->entry('welcome::app'))
        ->toThrow(ApplicationAssetManifestMissingException::class, 'Run php lpwork frontend:build');
});

it('reports missing source files before rendering asset tags', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/application-assets';
    $files = new Filesystem();
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: $basePath,
        entries: $entries,
        manifests: new ApplicationAssetManifestReader($basePath, $files),
        mode: ApplicationAssetRenderMode::DevServer,
        files: $files,
    );

    expect(fn() => $renderer->entry('welcome::app'))
        ->toThrow(ApplicationAssetSourceFileMissingException::class, 'Create the file or update the asset entry declaration');
});

it('reports missing manifest entries for declared source files', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/application-assets';
    $files = new Filesystem();
    $files->write($basePath . '/App/Modules/Welcome/resources/frontend/app.ts', "import './app.css';\n");
    $files->write($basePath . '/public/build/manifest.json', json_encode([], JSON_THROW_ON_ERROR));
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: $basePath,
        entries: $entries,
        manifests: new ApplicationAssetManifestReader($basePath, $files),
        mode: ApplicationAssetRenderMode::Manifest,
        files: $files,
    );

    expect(fn() => $renderer->entry('welcome::app'))
        ->toThrow(ApplicationAssetManifestEntryNotFoundException::class, 'Run php lpwork frontend:build');
});

it('reports missing built assets referenced by the manifest', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/application-assets';
    $files = new Filesystem();
    $files->write($basePath . '/App/Modules/Welcome/resources/frontend/app.ts', "import './app.css';\n");
    $files->write($basePath . '/public/build/manifest.json', json_encode([
        'App/Modules/Welcome/resources/frontend/app.ts' => [
            'file' => 'assets/welcome/app-DjVb6W5s.js',
        ],
    ], JSON_THROW_ON_ERROR));
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: $basePath,
        entries: $entries,
        manifests: new ApplicationAssetManifestReader($basePath, $files),
        mode: ApplicationAssetRenderMode::Manifest,
        files: $files,
    );

    expect(fn() => $renderer->entry('welcome::app'))
        ->toThrow(ApplicationAssetBuiltFileMissingException::class, 'Run php lpwork frontend:build');
});

it('reports unavailable Vite dev server connections', function (): void {
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $renderer = new ApplicationAssetRenderer(
        basePath: \Tests\support\ProjectPaths::root(),
        entries: $entries,
        manifests: new ApplicationAssetManifestReader(\Tests\support\ProjectPaths::root()),
        mode: ApplicationAssetRenderMode::DevServer,
        devServers: new class implements ApplicationAssetDevServerProbe {
            public function reachable(string $url): bool
            {
                return false;
            }
        },
    );

    expect(fn() => $renderer->entry('welcome::app'))
        ->toThrow(ApplicationAssetDevServerUnavailableException::class, 'Start it with php lpwork frontend:dev');
});
