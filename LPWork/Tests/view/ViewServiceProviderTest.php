<?php

declare(strict_types=1);

use App\Shared\Configs\CacheConfig;
use App\Shared\Configs\StorageConfig;
use App\Shared\Configs\ViewConfig;
use LPWork\Cache\CacheManager;
use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Config\Config;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;
use LPWork\View\Commands\ViewClearCommand;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\PhpViewEngine;
use LPWork\View\PhpViewEngineExtensions;
use LPWork\View\Providers\PhpViewEngineProvider;
use LPWork\View\Providers\ViewServiceProvider;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;
use Tests\support\ApplicationFactory;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('registers view services through the framework provider', function (): void {
    $app = ApplicationFactory::create();
    Config::initDefinitions([
        new StorageConfig(),
        new CacheConfig(),
        new ViewConfig(),
    ]);

    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new CacheServiceProvider());
    $app->register(new ViewServiceProvider());

    expect($app->container()->make(ViewEngine::class))->toBeInstanceOf(PhpViewEngine::class)
        ->and($app->container()->make(PhpViewEngineExtensions::class))->toBeInstanceOf(PhpViewEngineExtensions::class)
        ->and($app->container()->make(ViewFinder::class))->toBeInstanceOf(ViewFinder::class)
        ->and($app->container()->make(ViewFactory::class))->toBeInstanceOf(ViewFactory::class);
});

it('extends the PHP view engine through application view providers', function (): void {
    $app = ApplicationFactory::create();
    Config::initDefinitions([
        new StorageConfig(),
        new CacheConfig(),
        new ViewConfig(),
    ]);

    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new CacheServiceProvider());
    $app->register(new ViewServiceProvider());

    $app->register(new class extends PhpViewEngineProvider {
        /**
         * @return array<string, mixed>
         */
        protected function globals(): array
        {
            return [
                'appName' => 'LPWork',
            ];
        }

        /**
         * @return array<string, Closure>
         */
        protected function functions(): array
        {
            return [
                'upper' => static fn(string $value): string => strtoupper($value),
            ];
        }
    });

    $extensions = $app->container()->make(PhpViewEngineExtensions::class);

    expect($extensions)->toBeInstanceOf(PhpViewEngineExtensions::class);

    if (!$extensions instanceof PhpViewEngineExtensions) {
        return;
    }

    expect($extensions->globals())->toHaveKey('appName')
        ->and($extensions->functions())->toHaveKey('upper');
});

it('replaces the view engine through application view providers', function (): void {
    $app = ApplicationFactory::create();
    Config::initDefinitions([
        new StorageConfig(),
        new CacheConfig(),
        new ViewConfig(),
    ]);

    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new CacheServiceProvider());
    $app->register(new ViewServiceProvider());

    $app->register(new class extends PhpViewEngineProvider {
        /**
         * @return list<class-string<ViewEngine>>
         */
        protected function viewEngines(): array
        {
            return [
                Tests\support\view\UppercaseViewEngine::class,
            ];
        }
    });

    expect($app->container()->make(ViewEngine::class))->toBeInstanceOf(Tests\support\view\UppercaseViewEngine::class);
});

it('clears cached view lookup data through the view clear command', function (): void {
    $app = ApplicationFactory::create();
    Config::initDefinitions([
        new StorageConfig(),
        new CacheConfig(),
        new ViewConfig(),
    ]);

    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new CacheServiceProvider());
    $app->register(new ViewServiceProvider());

    $command = $app->container()->make(ViewClearCommand::class);
    $finder = $app->container()->make(ViewFinder::class);
    $cache = $app->container()->make(CacheManager::class);
    $streams = OutputStreams::create();

    expect($command)->toBeInstanceOf(ViewClearCommand::class)
        ->and($finder)->toBeInstanceOf(ViewFinder::class)
        ->and($cache)->toBeInstanceOf(CacheManager::class);

    if (!$command instanceof ViewClearCommand || !$finder instanceof ViewFinder || !$cache instanceof CacheManager) {
        return;
    }

    $cache->store('views')->put('view.path.test', 'cached');

    expect($command->handle(
        new Input(['lpwork', 'view:clear']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    ))->toBe(0)
        ->and($streams->stdout())->toContain('View cache cleared: views.')
        ->and($cache->store('views')->get('view.path.test', 'missing'))->toBe('missing');
});
