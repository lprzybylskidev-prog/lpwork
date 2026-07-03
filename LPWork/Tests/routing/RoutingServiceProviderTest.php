<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Routing\Exceptions\InvalidRoutingConfigException;
use LPWork\Routing\Providers\RoutingServiceProvider;
use LPWork\Routing\Router;
use LPWork\Url\Url;
use Tests\support\ConfigTestFiles;
use Tests\support\middleware\FirstMiddleware;
use Tests\support\middleware\SecondMiddleware;
use Tests\support\routing\TestController;

beforeEach(function (): void {
    Config::reset();
    Url::reset();
    ConfigTestFiles::resetDirectory();
});

afterEach(function (): void {
    Config::reset();
    Url::reset();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('defines the routing service provider', function (): void {
    expect(new RoutingServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers routing services and configures the Url facade', function (): void {
    ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['url' => 'https://lpwork.test'];\n");
    Config::init(ConfigTestFiles::directory());

    $container = new Container();

    new RoutingServiceProvider()->register($container);

    $router = $container->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if ($router instanceof Router) {
        $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');
    }

    expect(Url::route('posts.show', ['post' => 15]))->toBe('https://lpwork.test/posts/15');
});

it('registers route middleware aliases and groups from routing config', function (): void {
    ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['url' => 'https://lpwork.test'];\n");
    ConfigTestFiles::createConfig('routing.php', [
        'middleware' => [
            'global' => ['first'],
            'aliases' => [
                'first' => FirstMiddleware::class,
                'second' => SecondMiddleware::class,
            ],
            'groups' => [
                'web' => ['first', 'second'],
            ],
        ],
    ]);
    Config::init(ConfigTestFiles::directory());

    $container = new Container();

    new RoutingServiceProvider()->register($container);

    $router = $container->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $router->get('/profile', [TestController::class, 'index'])->middleware('web');

    expect($router->globalMiddlewareList())->toBe([
        FirstMiddleware::class,
    ])->and($router->routes()->match('GET', '/profile')->route()->middlewareList())->toBe([
        FirstMiddleware::class,
        SecondMiddleware::class,
    ]);
});

it('throws when routing middleware config is invalid', function (): void {
    ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['url' => 'https://lpwork.test'];\n");
    ConfigTestFiles::createConfig('routing.php', [
        'middleware' => [
            'aliases' => [
                'broken' => ['not-a-class'],
            ],
        ],
    ]);
    Config::init(ConfigTestFiles::directory());

    expect(fn() => new RoutingServiceProvider()->register(new Container()))
        ->toThrow(InvalidRoutingConfigException::class, 'Routing middleware aliases must be a map');
});

it('throws when global routing middleware config is invalid', function (): void {
    ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['url' => 'https://lpwork.test'];\n");
    ConfigTestFiles::createConfig('routing.php', [
        'middleware' => [
            'global' => [123],
        ],
    ]);
    Config::init(ConfigTestFiles::directory());

    expect(fn() => new RoutingServiceProvider()->register(new Container()))
        ->toThrow(InvalidRoutingConfigException::class, 'Global route middleware must be a list');
});
