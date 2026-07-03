<?php

declare(strict_types=1);

use LPWork\Console\Output;
use LPWork\Routing\RouteListRenderer;
use LPWork\Routing\Router;
use Tests\support\console\OutputStreams;
use Tests\support\middleware\FirstMiddleware;
use Tests\support\routing\TestController;

it('renders registered routes as a table', function (): void {
    $router = new Router();
    $router->get('/', [TestController::class, 'index'])->name('home');
    $router->post('/posts', [TestController::class, 'store'])->middleware(FirstMiddleware::class)->name('posts.store');
    $router->get('/health', static fn(): string => 'ok');
    $streams = OutputStreams::create();

    new RouteListRenderer()->render(
        $router->routes()->all(),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toContain('Registered routes:')
        ->and($streams->stdout())->toContain('| Method | URI     | Name        | Action')
        ->and($streams->stdout())->toContain('| GET    | /       | home        | Tests\support\routing\TestController@index')
        ->and($streams->stdout())->toContain('| POST   | /posts  | posts.store | Tests\support\routing\TestController@store')
        ->and($streams->stdout())->toContain(FirstMiddleware::class)
        ->and($streams->stdout())->toContain('| GET    | /health | -           | Closure')
        ->and($streams->stderr())->toBe('');
});

it('renders an empty route list message', function (): void {
    $streams = OutputStreams::create();

    new RouteListRenderer()->render(
        [],
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toBe("No routes registered.\n")
        ->and($streams->stderr())->toBe('');
});
