<?php

declare(strict_types=1);

use LPWork\Routing\Route;
use LPWork\Routing\RouteAction;
use Tests\support\routing\TestController;

it('sets and returns route names explicitly', function (): void {
    $route = new Route(['GET'], '/posts', new RouteAction(TestController::class, 'index'));

    expect($route->name())->toBeNull()
        ->and($route->setName('posts.index'))->toBe($route)
        ->and($route->name())->toBe('posts.index');
});

it('matches paths after constraints are changed', function (): void {
    $route = new Route(['GET'], '/posts/{post}', new RouteAction(TestController::class, 'show'));

    expect($route->matchesPath('/posts/abc'))->toBeTrue();

    $route->where('post', '[0-9]+');

    expect($route->matchesPath('/posts/abc'))->toBeFalse()
        ->and($route->matchesPath('/posts/15'))->toBeTrue()
        ->and($route->parameters('/posts/15'))->toBe(['post' => '15']);
});

it('matches optional route parameters', function (): void {
    $route = new Route(['GET'], '/docs/{section?}', new RouteAction(TestController::class, 'show'));

    expect($route->matchesPath('/docs'))->toBeTrue()
        ->and($route->matchesPath('/docs/routing'))->toBeTrue()
        ->and($route->parameterNames())->toBe(['section'])
        ->and($route->parameters('/docs'))->toBe([])
        ->and($route->parameters('/docs/routing'))->toBe(['section' => 'routing'])
        ->and($route->hasOptionalParameter('section'))->toBeTrue();
});
