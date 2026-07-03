<?php

declare(strict_types=1);

use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Http\Exceptions\MethodNotAllowedException;
use LPWork\Http\Exceptions\NotFoundException;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Commands\RouteClearCommand;
use LPWork\Routing\Exceptions\InvalidResourceRouteException;
use LPWork\Routing\Exceptions\InvalidRoutingConfigException;
use LPWork\Routing\Exceptions\RouteCacheException;
use LPWork\Routing\Exceptions\RouteFileNotFoundException;
use LPWork\Routing\RouteCache;
use LPWork\Routing\RouteCollection;
use LPWork\Routing\Router;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\OutputStreams;
use Tests\support\middleware\FirstMiddleware;
use Tests\support\middleware\SecondMiddleware;
use Tests\support\routing\TestController;

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('registers and matches RESTful routes', function (): void {
    $router = new Router();

    $router->get('/posts', [TestController::class, 'index'])->name('posts.index');
    $router->post('/posts', [TestController::class, 'store'])->name('posts.store');
    $router->put('/posts/{post}', [TestController::class, 'update'])->name('posts.update');
    $router->patch('/posts/{post}', [TestController::class, 'update'])->name('posts.update');
    $router->delete('/posts/{post}', [TestController::class, 'destroy'])->name('posts.destroy');
    $router->options('/posts', [TestController::class, 'index'])->name('posts.options');

    $match = $router->routes()->match('PATCH', '/posts/15');

    expect($match->route()->action()->controller())->toBe(TestController::class)
        ->and($match->route()->action()->method())->toBe('update')
        ->and($match->parameters())->toBe(['post' => '15']);
});

it('keeps pending route naming as separate commands and queries', function (): void {
    $router = new Router();

    $pending = $router->get('/named', [TestController::class, 'index']);

    expect($pending->routeName())->toBeNull()
        ->and($pending->name('named.route'))->toBe($pending)
        ->and($pending->routeName())->toBe('named.route');
});

it('registers routes for multiple or any methods', function (): void {
    $router = new Router();

    $router->match(['GET', 'POST'], '/login', [TestController::class, 'store'])->name('login');
    $router->any('/anything', [TestController::class, 'index'])->name('anything');

    expect($router->routes()->match('POST', '/login')->route()->name())->toBe('login')
        ->and($router->routes()->match('DELETE', '/anything')->route()->name())->toBe('anything')
        ->and($router->routes()->match('HEAD', '/anything')->route()->name())->toBe('anything');
});

it('supports explicit and implicit HEAD routes', function (): void {
    $router = new Router();

    $router->get('/health', [TestController::class, 'index'])->name('health');
    $router->get('/status', [TestController::class, 'index'])->name('status.get');
    $router->head('/status', [TestController::class, 'show'])->name('status.head');

    expect($router->routes()->match('HEAD', '/health')->route()->name())->toBe('health')
        ->and($router->routes()->match('HEAD', '/status')->route()->name())->toBe('status.head');

    try {
        $router->routes()->match('POST', '/health');
    } catch (MethodNotAllowedException $exception) {
        expect($exception->headers())->toBe(['Allow' => 'GET, HEAD']);
    }
});

it('registers closure routes', function (): void {
    $router = new Router();

    $router->get('/health', static fn(): HttpResponse => HttpResponse::text('ok'))->name('health');

    $match = $router->routes()->match('GET', '/health');

    expect($match->route()->action()->isClosure())->toBeTrue()
        ->and($match->route()->name())->toBe('health');
});

it('registers route groups with prefix names and middleware metadata', function (): void {
    $router = new Router();
    $router->aliasMiddleware('auth', FirstMiddleware::class);
    $router->aliasMiddleware('can:view-reports', SecondMiddleware::class);
    $router->middlewareGroup('web', FirstMiddleware::class);

    $router->group([
        'prefix' => '/admin',
        'name' => 'admin.',
        'middleware' => ['web', 'auth'],
    ], function (Router $router): void {
        $router->group([
            'prefix' => '/reports',
            'name' => 'reports.',
            'middleware' => 'can:view-reports',
        ], function (Router $router): void {
            $router->get('/daily', [TestController::class, 'index'])->name('daily');
        });
    });

    $match = $router->routes()->match('GET', '/admin/reports/daily');

    expect($match->route()->name())->toBe('admin.reports.daily')
        ->and($match->route()->middlewareList())->toBe([
            FirstMiddleware::class,
            FirstMiddleware::class,
            SecondMiddleware::class,
        ]);
});

it('expands middleware aliases and groups when routes are registered', function (): void {
    $router = new Router();
    $router->aliasMiddleware('first', FirstMiddleware::class);
    $router->aliasMiddleware('second', SecondMiddleware::class);
    $router->middlewareGroup('web', ['first', 'second']);

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->get('/profile', [TestController::class, 'index'])
            ->middleware('first')
            ->name('profile');
    });

    $match = $router->routes()->match('GET', '/profile');

    expect($match->route()->middlewareList())->toBe([
        FirstMiddleware::class,
        SecondMiddleware::class,
        FirstMiddleware::class,
    ]);
});

it('rejects unknown route middleware declarations when routes are registered', function (): void {
    $router = new Router();

    expect(fn() => $router->get('/profile', [TestController::class, 'index'])->middleware('web'))
        ->toThrow(InvalidRoutingConfigException::class, 'Route middleware class or alias is not registered: web.');
});

it('rejects invalid route group names', function (): void {
    $router = new Router();

    expect(fn() => $router->group(['name' => 'admin panel.'], static function (): void {}))
        ->toThrow(InvalidRoutingConfigException::class, 'Route group name is invalid');
});

it('matches constrained route parameters', function (): void {
    $router = new Router();

    $router->get('/posts/{post}', [TestController::class, 'show'])
        ->where('post', '[0-9]+')
        ->name('posts.show');

    expect($router->routes()->match('GET', '/posts/123')->parameter('post'))->toBe('123');

    expect(fn() => $router->routes()->match('GET', '/posts/abc'))
        ->toThrow(NotFoundException::class);
});

it('throws not found and method not allowed HTTP exceptions', function (): void {
    $router = new Router();

    $router->get('/posts', [TestController::class, 'index']);
    $router->post('/posts', [TestController::class, 'store']);

    expect(fn() => $router->routes()->match('GET', '/missing'))
        ->toThrow(NotFoundException::class);

    expect(fn() => $router->routes()->match('DELETE', '/posts'))
        ->toThrow(MethodNotAllowedException::class);

    try {
        $router->routes()->match('DELETE', '/posts');
    } catch (MethodNotAllowedException $exception) {
        expect($exception->headers())->toBe(['Allow' => 'GET, HEAD, POST']);
    }
});

it('loads route files with the current router instance', function (): void {
    $router = new Router();
    $path = tempnam(sys_get_temp_dir(), 'lpwork-routes-');

    if ($path === false) {
        throw new RuntimeException('Could not create temporary route file.');
    }

    try {
        file_put_contents($path, "<?php\n\nuse Tests\\support\\routing\\TestController;\n\n\$router->get('/loaded', [TestController::class, 'index'])->name('loaded');\n");

        $router->load($path);

        expect($router->routes()->match('GET', '/loaded')->route()->name())->toBe('loaded');
    } finally {
        unlink($path);
    }
});

it('throws when loading a missing route file', function (): void {
    expect(fn() => new Router()->load(sys_get_temp_dir() . '/missing-lpwork-route-file.php'))
        ->toThrow(RouteFileNotFoundException::class);
});

it('writes and loads controller routes through the route cache', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $router = new Router();
    $router->aliasMiddleware('auth', FirstMiddleware::class);
    $router->middlewareGroup('web', SecondMiddleware::class);

    $router->get('/posts/{post}', [TestController::class, 'show'])
        ->middleware(['web', 'auth'])
        ->where('post', '[0-9]+')
        ->name('posts.show');

    $cache = new RouteCache($environment->basePath(), 'cache/routes.php');
    $cache->write($router->routes());

    $routes = new RouteCollection();
    $cache->loadInto($routes);
    $match = $routes->match('GET', '/posts/15');

    expect($match->route()->name())->toBe('posts.show')
        ->and($match->route()->action()->controller())->toBe(TestController::class)
        ->and($match->route()->action()->method())->toBe('show')
        ->and($match->route()->middlewareList())->toBe([SecondMiddleware::class, FirstMiddleware::class])
        ->and($match->parameters())->toBe(['post' => '15']);
});

it('refuses to cache closure routes', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $router = new Router();

    $router->get('/health', static fn(): HttpResponse => HttpResponse::text('ok'))->name('health');

    expect(fn() => new RouteCache($environment->basePath(), 'cache/routes.php')->write($router->routes()))
        ->toThrow(RouteCacheException::class, 'Cannot cache closure route [/health].');
});

it('reports corrupted route cache records explicitly', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('cache/routes.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        return [
            ['methods' => [], 'path' => '/broken'],
        ];
        PHP);

    expect(fn() => new RouteCache($environment->basePath(), 'cache/routes.php')->loadInto(new RouteCollection()))
        ->toThrow(RouteCacheException::class, 'Route cache file is invalid: ' . $environment->basePath() . '/cache/routes.php.');
});

it('clears route cache through the route clear command', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $router = new Router();
    $cache = new RouteCache($environment->basePath(), 'cache/routes.php');
    $streams = OutputStreams::create();

    $router->get('/posts', [TestController::class, 'index'])->name('posts.index');
    $cache->write($router->routes());

    expect($environment->basePath() . '/cache/routes.php')->toBeFile()
        ->and(new RouteClearCommand($cache)->handle(
            new Input(['lpwork', 'route:clear']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        ))->toBe(0)
        ->and(is_file($environment->basePath() . '/cache/routes.php'))->toBeFalse()
        ->and($streams->stdout())->toContain('Route cache cleared successfully.');
});

it('registers resource routes', function (): void {
    $router = new Router();

    $router->resource('posts', TestController::class);

    expect($router->routes()->match('GET', '/posts')->route()->name())->toBe('posts.index')
        ->and($router->routes()->match('GET', '/posts/create')->route()->name())->toBe('posts.create')
        ->and($router->routes()->match('POST', '/posts')->route()->name())->toBe('posts.store')
        ->and($router->routes()->match('GET', '/posts/1')->route()->name())->toBe('posts.show')
        ->and($router->routes()->match('GET', '/posts/1/edit')->route()->name())->toBe('posts.edit')
        ->and($router->routes()->match('PUT', '/posts/1')->route()->name())->toBe('posts.update')
        ->and($router->routes()->match('PATCH', '/posts/1')->route()->name())->toBe('posts.update')
        ->and($router->routes()->match('DELETE', '/posts/1')->route()->name())->toBe('posts.destroy');
});

it('registers resource routes with only except and custom parameter names', function (): void {
    $router = new Router();

    $router->resource('articles', TestController::class, only: ['index', 'show'], parameter: 'article');
    $router->resource('photos', TestController::class, except: ['destroy']);

    expect($router->routes()->match('GET', '/articles')->route()->name())->toBe('articles.index')
        ->and($router->routes()->match('GET', '/articles/7')->parameters())->toBe(['article' => '7'])
        ->and($router->routes()->match('GET', '/photos')->route()->name())->toBe('photos.index');

    expect(fn() => $router->routes()->match('POST', '/articles'))
        ->toThrow(MethodNotAllowedException::class);

    expect(fn() => $router->routes()->match('DELETE', '/photos/9'))
        ->toThrow(MethodNotAllowedException::class);
});

it('throws when resource routes are filtered by an unknown action', function (): void {
    $router = new Router();

    expect(fn() => $router->resource('posts', TestController::class, only: ['publish']))
        ->toThrow(InvalidResourceRouteException::class, 'Unknown resource route action [publish].');
});
