<?php

declare(strict_types=1);

use LPWork\Console\Commands\RouteListCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Routing\Router;
use Tests\support\console\OutputStreams;
use Tests\support\routing\TestController;

it('displays the registered route list', function (): void {
    $router = new Router();
    $router->get('/users/{id}', [TestController::class, 'show'])->name('users.show');
    $streams = OutputStreams::create();

    $exitCode = new RouteListCommand($router->routes())->handle(
        new Input(['lpwork', 'route:list']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('Registered routes:')
        ->and($streams->stdout())->toContain('| GET    | /users/{id} | users.show | Tests\support\routing\TestController@show')
        ->and($streams->stderr())->toBe('');
});
