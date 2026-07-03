<?php

declare(strict_types=1);

use LPWork\Http\FormErrors;
use LPWork\Http\OldInput;
use LPWork\Http\Redirect;
use LPWork\Requests\HttpRequest;
use LPWork\Routing\Router;
use LPWork\Security\HmacSigner;
use LPWork\Security\SignedUrl;
use LPWork\Session\Session;
use LPWork\Storage\StorageManager;
use LPWork\Support\Helpers;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\Translator;
use LPWork\Url\Url;
use LPWork\Url\UrlGenerator;
use LPWork\View\PhpViewEngine;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\routing\TestController;
use Tests\support\testing\Security\TestApplicationKeys;

beforeEach(function (): void {
    Url::reset();
});

afterEach(function (): void {
    Url::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('delegates method and CSRF input helpers to their framework modules', function (): void {
    $session = new LPWork\Session\Session();

    $csrfInput = Helpers::csrfInput($session);
    $token = $session->get('_csrf_token');

    expect(Helpers::methodInput('patch'))->toBe('<input type="hidden" name="_method" value="PATCH">')
        ->and($token)->toBeString();

    if (!is_string($token)) {
        return;
    }

    expect($csrfInput)->toBe(sprintf('<input type="hidden" name="_token" value="%s">', $token));
});

it('delegates URL helpers to the configured URL facade', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');

    Url::setGenerator(new UrlGenerator($router->routes(), 'https://lpwork.test'));

    expect(Helpers::route('posts.show', ['post' => 15]))->toBe('https://lpwork.test/posts/15')
        ->and(Helpers::path('posts.show', ['post' => 15]))->toBe('/posts/15')
        ->and(Helpers::url('/admin', ['page' => 2]))->toBe('https://lpwork.test/admin?page=2');
});

it('delegates signed URL helpers to the configured URL facade', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()));

    Url::setGenerator(new UrlGenerator($router->routes(), 'https://lpwork.test', $signed));

    expect($signed->verify(Helpers::signedRoute('posts.show', ['post' => 15])))->toBeTrue()
        ->and($signed->verify(Helpers::temporarySignedRoute('posts.show', 9999999999, ['post' => 15])))->toBeTrue()
        ->and($signed->verify(Helpers::signedUrl('/admin')))->toBeTrue()
        ->and($signed->verify(Helpers::temporarySignedUrl('/admin', 9999999999)))->toBeTrue();
});

it('delegates storage URL helpers to the storage manager', function (): void {
    $storage = new StorageManager([
        'default' => 'public',
        'disks' => [
            'public' => [
                'driver' => 'memory',
                'url' => '/storage',
            ],
            'cdn' => [
                'driver' => 'memory',
                'url' => 'https://cdn.example.test/storage',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(Helpers::storageUrl($storage, 'avatars/me.png'))->toBe('/storage/avatars/me.png')
        ->and(Helpers::storageUrl($storage, 'avatars/me.png', 'cdn'))->toBe('https://cdn.example.test/storage/avatars/me.png');
});

it('delegates redirect and old input helpers to their framework modules', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');
    Url::setGenerator(new UrlGenerator($router->routes(), 'https://lpwork.test'));

    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/posts',
        'HTTP_REFERER' => '/form',
    ]);
    $session = new Session();

    Redirect::withInput(Helpers::redirect('/posts'), $session, [
        'title' => 'Draft',
        'token' => 'secret',
        'author' => [
            'name' => 'Ada',
            'role' => 'editor',
        ],
    ], ['token', 'author.role']);
    Redirect::withErrors(Helpers::redirect('/posts'), $session, [
        'title' => ['Title is required.'],
        'author' => [
            'name' => ['Author name is required.'],
        ],
    ]);

    expect(Helpers::redirect('/dashboard')->header('Location'))->toBe('/dashboard')
        ->and(Helpers::redirectRoute('posts.show', ['post' => 15])->header('Location'))->toBe('https://lpwork.test/posts/15')
        ->and(Helpers::back($request)->header('Location'))->toBe('/form')
        ->and(Helpers::old($session, 'title'))->toBe('Draft')
        ->and(Helpers::old($session, 'token', 'missing'))->toBe('missing')
        ->and(Helpers::old($session, 'author.name'))->toBe('Ada')
        ->and(Helpers::old($session, 'author.role', 'missing'))->toBe('missing')
        ->and(Helpers::error($session, 'title'))->toBe(['Title is required.'])
        ->and(Helpers::error($session, 'author.name'))->toBe(['Author name is required.']);
});

it('builds fluent redirects with flashed input and errors', function (): void {
    $router = new Router();
    $router->get('/posts/{post}', [TestController::class, 'show'])->name('posts.show');
    Url::setGenerator(new UrlGenerator($router->routes(), 'https://lpwork.test'));

    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/posts',
        'HTTP_REFERER' => '/form',
    ]);
    $session = new Session();

    $response = Helpers::redirects($session)
        ->route('posts.show', ['post' => 15])
        ->with('status', 'Saved')
        ->withInput([
            'title' => 'Draft',
            'password' => 'secret',
            'author' => [
                'name' => 'Ada',
                'email' => 'ada@example.com',
            ],
        ], ['password', 'author.email'])
        ->withErrors([
            'author' => [
                'email' => ['Email is invalid.'],
            ],
        ])
        ->response();

    expect($response->header('Location'))->toBe('https://lpwork.test/posts/15')
        ->and($session->get('status'))->toBe('Saved')
        ->and(OldInput::get($session, 'title'))->toBe('Draft')
        ->and(OldInput::get($session, 'password', 'missing'))->toBe('missing')
        ->and(OldInput::get($session, 'author.name'))->toBe('Ada')
        ->and(FormErrors::get($session, 'author.email'))->toBe(['Email is invalid.'])
        ->and(Redirect::withSession($session)->back($request)->response()->header('Location'))->toBe('/form');
});

it('delegates view rendering to the configured view factory', function (): void {
    $basePath = sys_get_temp_dir() . '/lpwork_helper_views_' . uniqid('', true);

    if (!mkdir($basePath . '/views', recursive: true)) {
        throw new RuntimeException('Could not create temporary view directory.');
    }

    try {
        file_put_contents($basePath . '/views/card.php', '<p><?= $view->e($title) ?></p>');

        $views = new ViewFactory(new ViewFinder(['views'], $basePath), new PhpViewEngine());

        expect(Helpers::view($views, 'card', ['title' => '<Hello>']))->toBe('<p>&lt;Hello&gt;</p>');
    } finally {
        unlink($basePath . '/views/card.php');
        rmdir($basePath . '/views');
        rmdir($basePath);
    }
});

it('delegates translation helpers to the configured translator', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"validation.required":"Pole :field jest wymagane.","Save":"Zapisz"}');

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'pl_PL');

    expect(Helpers::trans($translator, 'validation.required', ['field' => 'email']))->toBe('Pole email jest wymagane.')
        ->and(Helpers::transText($translator, 'Save'))->toBe('Zapisz');
});
