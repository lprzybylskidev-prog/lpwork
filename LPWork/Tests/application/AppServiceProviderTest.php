<?php

declare(strict_types=1);

use App\AppServiceProvider;
use App\Modules\Welcome\Assets\AssetsProvider;
use App\Modules\Welcome\Broadcasting\BroadcastingProvider;
use App\Modules\Welcome\Configs\ConfigsProvider as WelcomeConfigsProvider;
use App\Modules\Welcome\Controllers\HomeController;
use App\Modules\Welcome\Routes\RoutesProvider;
use App\Modules\Welcome\Translation\TranslationProvider;
use App\Modules\Welcome\View\ViewProvider;
use App\Modules\Welcome\WelcomeServiceProvider;
use App\Shared\Configs\ConfigsProvider;
use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Config\Config;
use LPWork\Console\Providers\ConsoleServiceProvider;
use LPWork\Environment\Environment;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Frontend\Providers\FrontendServiceProvider;
use LPWork\Routing\Providers\RoutingServiceProvider;
use LPWork\Routing\Router;
use LPWork\Storage\Providers\StorageServiceProvider;
use LPWork\View\Providers\ViewServiceProvider;
use Tests\support\ApplicationFactory;

beforeEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    Environment::reset();
    Config::reset();
});

it('defines the main application service provider', function (): void {
    $provider = new AppServiceProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('declares only explicit application module providers', function (): void {
    $provider = new AppServiceProvider();

    expect($provider->providerClasses())->toBe([
        WelcomeServiceProvider::class,
    ]);
});

it('keeps the welcome module free of empty placeholder providers', function (): void {
    $provider = new WelcomeServiceProvider();

    expect($provider->providerClasses())->toBe([
        AssetsProvider::class,
        BroadcastingProvider::class,
        WelcomeConfigsProvider::class,
        RoutesProvider::class,
        TranslationProvider::class,
        ViewProvider::class,
    ]);
});

it('registers application configuration through the application config provider', function (): void {
    $app = ApplicationFactory::create();

    Environment::init($app->basePath('.env'));
    $app->register(new FoundationServiceProvider($app));
    $app->register(new ConfigsProvider($app));

    expect(Config::getString('app.url'))->toBe('http://localhost')
        ->and(Config::getString('cache.default'))->toBe('framework')
        ->and(Config::getString('storage.default'))->toBe('local')
        ->and(Config::getString('view.cache_store'))->toBe('views');
});

it('registers explicit application modules through the container', function (): void {
    $app = ApplicationFactory::create();
    $provider = new AppServiceProvider();

    Environment::init($app->basePath('.env'));
    $app->register(new FoundationServiceProvider($app));
    $app->register(new ConfigsProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new CacheServiceProvider());
    $app->register(new FrontendServiceProvider());
    $app->register(new ConsoleServiceProvider());
    $app->register(new RoutingServiceProvider());
    $app->register(new ViewServiceProvider());
    $app->register($provider);

    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if ($router instanceof Router) {
        $route = $router->routes()->match('GET', '/')->route();

        expect($route->name())->toBe('home')
            ->and($route->action()->controller())->toBe(HomeController::class);
    }
});
