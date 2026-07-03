<?php

declare(strict_types=1);

use LPWork\Routing\Router;
use LPWork\Security\HmacSigner;
use LPWork\Security\SignedUrl;
use LPWork\Url\Exceptions\MissingRouteParameterException;
use LPWork\Url\Exceptions\RouteNameNotFoundException;
use LPWork\Url\Exceptions\SignedUrlNotConfiguredException;
use LPWork\Url\Exceptions\UrlGeneratorNotConfiguredException;
use LPWork\Url\Url;
use LPWork\Url\UrlGenerator;
use Tests\support\queue\MutableClock;
use Tests\support\routing\TestController;
use Tests\support\testing\Security\TestApplicationKeys;

beforeEach(function (): void {
    Url::reset();
});

afterEach(function (): void {
    Url::reset();
});

it('generates absolute URLs for named routes', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');

    $url = new UrlGenerator($router->routes(), 'https://lpwork.test');

    expect($url->route('posts.show', ['post' => 15]))->toBe('https://lpwork.test/posts/15');
});

it('generates paths for named routes', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');

    $url = new UrlGenerator($router->routes(), 'https://lpwork.test');

    expect($url->path('posts.show', ['post' => 15]))
        ->toBe('/posts/15')
        ->and($url->route('posts.show', ['post' => 15], absolute: false))
        ->toBe('/posts/15');
});

it('adds unused parameters as query string', function (): void {
    $router = new Router();
    $router->get('/posts', [TestController::class, 'index'])->name('posts.index');

    $url = new UrlGenerator($router->routes(), 'https://lpwork.test/');

    expect($url->route('posts.index', ['page' => 2, 'filter' => 'published']))
            ->toBe('https://lpwork.test/posts?page=2&filter=published');
});

it('generates named route URLs with optional parameters', function (): void {
    $router = new Router();
    $router->get('/docs/{section?}', [TestController::class, 'show'])->name('docs.section');

    $url = new UrlGenerator($router->routes(), 'https://lpwork.test');

    expect($url->path('docs.section'))->toBe('/docs')
        ->and($url->path('docs.section', ['section' => 'routing']))->toBe('/docs/routing')
        ->and($url->route('docs.section', ['section' => 'routing']))->toBe('https://lpwork.test/docs/routing');
});

it('generates absolute URLs and paths for arbitrary paths', function (): void {
    $url = new UrlGenerator(new Router()->routes(), 'https://lpwork.test');

    expect($url->to('/admin'))->toBe('https://lpwork.test/admin')
        ->and($url->to('admin', ['page' => 2]))->toBe('https://lpwork.test/admin?page=2')
        ->and($url->to('/admin', absolute: false))->toBe('/admin');
});

it('uses the static Url facade', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');

    Url::setGenerator(new UrlGenerator($router->routes(), 'https://lpwork.test'));

    expect(Url::route('posts.show', ['post' => 1]))->toBe('https://lpwork.test/posts/1')
        ->and(Url::path('posts.show', ['post' => 1]))->toBe('/posts/1')
        ->and(Url::to('/admin'))->toBe('https://lpwork.test/admin')
        ->and(Url::to('/admin', absolute: false))->toBe('/admin');
});

it('generates signed and temporary signed named route URLs', function (): void {
    $clock = new MutableClock(1000);
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()), $clock);

    $url = new UrlGenerator($router->routes(), 'https://lpwork.test', $signed);

    $signedUrl = $url->signedRoute('posts.show', ['post' => 15, 'page' => 2]);
    $temporaryUrl = $url->temporarySignedRoute('posts.show', 1060, ['post' => 15]);

    expect($signedUrl)->toStartWith('https://lpwork.test/posts/15?page=2&signature=')
        ->and($signed->verify($signedUrl))->toBeTrue()
        ->and($temporaryUrl)->toStartWith('https://lpwork.test/posts/15?expires=1060&signature=')
        ->and($signed->verify($temporaryUrl))->toBeTrue();
});

it('generates signed arbitrary URLs', function (): void {
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()));
    $url = new UrlGenerator(new Router()->routes(), 'https://lpwork.test', $signed);

    $signedUrl = $url->signedTo('/download', ['file' => 'report.pdf']);

    expect($signedUrl)->toStartWith('https://lpwork.test/download?file=report.pdf&signature=')
        ->and($signed->verify($signedUrl))->toBeTrue();
});

it('uses the static Url facade for signed URLs', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()));

    Url::setGenerator(new UrlGenerator($router->routes(), 'https://lpwork.test', $signed));

    expect($signed->verify(Url::signedRoute('posts.show', ['post' => 1])))->toBeTrue()
        ->and($signed->verify(Url::temporarySignedRoute('posts.show', 9999999999, ['post' => 1])))->toBeTrue()
        ->and($signed->verify(Url::signedTo('/admin')))->toBeTrue()
        ->and($signed->verify(Url::temporarySignedTo('/admin', 9999999999)))->toBeTrue();
});

it('throws when the static Url facade has no generator', function (): void {
    expect(fn() => Url::route('posts.index'))
        ->toThrow(UrlGeneratorNotConfiguredException::class)
        ->and(fn() => Url::path('posts.index'))
        ->toThrow(UrlGeneratorNotConfiguredException::class)
        ->and(fn() => Url::to('/posts'))
        ->toThrow(UrlGeneratorNotConfiguredException::class);
});

it('throws when signed URL support is not configured on the URL generator', function (): void {
    $router = new Router();
    $router->get('/posts', [TestController::class, 'index'])->name('posts.index');
    $url = new UrlGenerator($router->routes(), 'https://lpwork.test');

    expect(fn() => $url->signedRoute('posts.index'))
        ->toThrow(SignedUrlNotConfiguredException::class)
        ->and(fn() => $url->signedTo('/download'))
        ->toThrow(SignedUrlNotConfiguredException::class);
});

it('throws when a named route is missing', function (): void {
    expect(fn() => new UrlGenerator(new Router()->routes())->route('missing'))
        ->toThrow(RouteNameNotFoundException::class);
});

it('throws when a required route parameter is missing', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');

    expect(fn() => new UrlGenerator($router->routes())->route('posts.show'))
        ->toThrow(MissingRouteParameterException::class);
});
