<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Frontend\Exceptions\InvalidAssetEntryDeclarationException;
use LPWork\Frontend\Providers\AssetEntrypointsProvider;
use LPWork\Frontend\Providers\FrontendServiceProvider;

it('registers explicitly declared application asset entries', function (): void {
    $container = new Container();
    new FrontendServiceProvider()->register($container);

    $provider = new class extends AssetEntrypointsProvider {
        /**
         * @return array<string, string>
         */
        protected function assetEntries(): array
        {
            return [
                'dashboard::app' => 'App/Modules/Dashboard/resources/frontend/app.ts',
            ];
        }
    };

    $provider->register($container);

    $entries = $container->make(AssetEntryRegistry::class);

    expect($entries)->toBeInstanceOf(AssetEntryRegistry::class);

    if ($entries instanceof AssetEntryRegistry) {
        expect($entries->has('dashboard::app'))->toBeTrue()
            ->and($entries->get('dashboard::app')?->name())->toBe('dashboard::app')
            ->and($entries->get('dashboard::app')?->sourcePath())->toBe('App/Modules/Dashboard/resources/frontend/app.ts')
            ->and(array_keys($entries->all()))->toBe(['dashboard::app']);
    }
});

it('rejects invalid asset entry declarations', function (string $name, string $sourcePath, string $message): void {
    $entries = new AssetEntryRegistry();

    expect(fn() => $entries->add($name, $sourcePath))
        ->toThrow(InvalidAssetEntryDeclarationException::class, $message);
})->with([
    'missing namespace separator' => ['welcome', 'App/Modules/Welcome/resources/frontend/app.ts', 'Invalid asset entry name [welcome].'],
    'absolute source path' => ['welcome::app', '/App/Modules/Welcome/resources/frontend/app.ts', 'Invalid source path [/App/Modules/Welcome/resources/frontend/app.ts]'],
    'parent source path segment' => ['welcome::app', '../Welcome/resources/frontend/app.ts', 'Invalid source path [../Welcome/resources/frontend/app.ts]'],
]);

it('rejects duplicate asset entry names', function (): void {
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');

    expect(fn() => $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/other.ts'))
        ->toThrow(InvalidAssetEntryDeclarationException::class, 'Asset entry [welcome::app] is already registered.');
});
