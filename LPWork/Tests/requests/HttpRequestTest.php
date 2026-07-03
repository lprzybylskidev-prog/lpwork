<?php

declare(strict_types=1);

use LPWork\Requests\Contracts\Request;
use LPWork\Requests\HttpRequest;
use LPWork\Requests\UploadedFile;
use LPWork\Session\Exceptions\SessionNotAttachedException;
use LPWork\Session\Session;

it('wraps Http request data from arrays', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'post',
            'REQUEST_URI' => '/articles/42?preview=1',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'CONTENT_TYPE' => 'application/json',
        ],
        query: ['preview' => '1'],
        input: ['title' => 'Hello'],
        cookies: ['session' => 'abc'],
        files: [
            'avatar' => [
                'tmp_name' => '/tmp/avatar',
                'name' => 'avatar.png',
                'type' => 'image/png',
                'size' => 512,
                'error' => UPLOAD_ERR_OK,
            ],
        ],
        body: '{"title":"Hello"}',
    );

    expect($request)->toBeInstanceOf(Request::class)
        ->and($request->method())->toBe('POST')
        ->and($request->uri())->toBe('/articles/42?preview=1')
        ->and($request->path())->toBe('/articles/42')
        ->and($request->queryValue('preview'))->toBe('1')
        ->and($request->inputValue('title'))->toBe('Hello')
        ->and($request->cookie('session'))->toBe('abc')
        ->and($request->file('avatar'))->toBeInstanceOf(UploadedFile::class)
        ->and($request->header('accept'))->toBe('application/json')
        ->and($request->header('X-Requested-With'))->toBe('XMLHttpRequest')
        ->and($request->header('Content-Type'))->toBe('application/json')
        ->and($request->body())->toBe('{"title":"Hello"}');
});

it('wraps uploaded file input in uploaded file objects', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/upload',
        ],
        files: [
            'avatar' => [
                'tmp_name' => '/tmp/avatar',
                'name' => 'avatar.PNG',
                'type' => 'image/png',
                'size' => 512,
                'error' => UPLOAD_ERR_OK,
            ],
        ],
    );

    $avatar = $request->file('avatar');

    expect($avatar)->toBeInstanceOf(UploadedFile::class)
        ->and($avatar?->temporaryPath())->toBe('/tmp/avatar')
        ->and($avatar?->clientName())->toBe('avatar.PNG')
        ->and($avatar?->clientMimeType())->toBe('image/png')
        ->and($avatar?->size())->toBe(512)
        ->and($avatar?->error())->toBe(UPLOAD_ERR_OK)
        ->and($avatar?->isValid())->toBeTrue()
        ->and($avatar?->clientExtension())->toBe('png');
});

it('normalizes nested uploaded file input and omits empty uploads', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/upload',
        ],
        files: [
            'photos' => [
                'tmp_name' => ['/tmp/first', ''],
                'name' => ['first.jpg', 'empty.jpg'],
                'type' => ['image/jpeg', 'image/jpeg'],
                'size' => [100, 0],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
            ],
        ],
    );

    expect($request->file('photos.0'))->toBeInstanceOf(UploadedFile::class)
        ->and($request->file('photos.0')?->clientName())->toBe('first.jpg')
        ->and($request->file('photos.1'))->toBeNull();
});

it('uses safe Http request defaults', function (): void {
    $request = HttpRequest::fromArrays(server: []);

    expect($request->method())->toBe('GET')
        ->and($request->uri())->toBe('/')
        ->and($request->path())->toBe('/')
        ->and($request->query())->toBe([])
        ->and($request->input())->toBe([])
        ->and($request->cookies())->toBe([])
        ->and($request->files())->toBe([])
        ->and($request->headers())->toBe([])
        ->and($request->body())->toBe('');
});

it('normalizes headers passed to the constructor', function (): void {
    $request = new HttpRequest(
        method: 'GET',
        uri: '/',
        path: '/',
        headers: [
            'content_type' => 'application/json',
            'x-requested-with' => 'XMLHttpRequest',
        ],
    );

    expect($request->headers())->toBe([
        'Content-Type' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->and($request->header('CONTENT-TYPE'))->toBe('application/json')
        ->and($request->header('x_requested_with'))->toBe('XMLHttpRequest');
});

it('exposes normalized HTTP security context values', function (): void {
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/upload',
        'HTTP_HOST' => 'Example.com:8443',
        'HTTP_X_FORWARDED_HOST' => 'Forwarded.example:443, proxy.local',
        'HTTP_X_FORWARDED_PROTO' => 'https, http',
        'CONTENT_LENGTH' => '512',
        'REMOTE_ADDR' => '10.0.0.1',
    ]);

    expect($request->host())->toBe('example.com')
        ->and($request->forwardedHost())->toBe('forwarded.example')
        ->and($request->forwardedScheme())->toBe('https')
        ->and($request->contentLength())->toBe(512)
        ->and($request->clientIp())->toBe('10.0.0.1')
        ->and($request->scheme())->toBe('http');
});

it('exposes web request convenience values', function (): void {
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'post',
        'REQUEST_URI' => '/articles?preview=1',
        'HTTP_HOST' => 'example.com',
        'HTTP_ACCEPT' => 'application/vnd.api+json; version=1',
        'CONTENT_TYPE' => 'application/json; charset=UTF-8',
        'HTTPS' => 'on',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    expect($request->isMethod('POST'))->toBeTrue()
        ->and($request->isJson())->toBeTrue()
        ->and($request->expectsJson())->toBeTrue()
        ->and($request->url())->toBe('https://example.com/articles')
        ->and($request->fullUrl())->toBe('https://example.com/articles?preview=1')
        ->and($request->ip())->toBe('127.0.0.1');
});

it('detects HTTPS from server values', function (): void {
    expect(HttpRequest::fromArrays(['REQUEST_URI' => '/', 'HTTPS' => 'on'])->scheme())->toBe('https')
        ->and(HttpRequest::fromArrays(['REQUEST_URI' => '/', 'REQUEST_SCHEME' => 'https'])->scheme())->toBe('https')
        ->and(HttpRequest::fromArrays(['REQUEST_URI' => '/', 'SERVER_PORT' => '443'])->scheme())->toBe('https');
});

it('attaches session to Http request clones', function (): void {
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/profile',
    ]);

    $session = new Session(['user_id' => 15]);

    expect($request->withSession($session)->session())->toBe($session);
});

it('replaces input on Http request clones', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/articles',
        ],
        input: ['title' => 'Form'],
    );

    $clone = $request->withInput(['title' => 'JSON']);

    expect($clone->inputValue('title'))->toBe('JSON')
        ->and($request->inputValue('title'))->toBe('Form');
});

it('reads typed input values with dot notation', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/articles',
        ],
        input: [
            'title' => 'Draft',
            'published' => 'yes',
            'views' => '15',
            'rating' => '4.5',
            'author' => [
                'name' => 'Ada',
                'roles' => ['editor'],
            ],
            'empty_string' => '',
            'empty_array' => [],
        ],
    );

    expect($request->inputValue('author.name'))->toBe('Ada')
        ->and($request->string('title'))->toBe('Draft')
        ->and($request->integer('views'))->toBe(15)
        ->and($request->float('rating'))->toBe(4.5)
        ->and($request->boolean('published'))->toBeTrue()
        ->and($request->array('author.roles'))->toBe(['editor'])
        ->and($request->string('author.roles', 'fallback'))->toBe('fallback')
        ->and($request->integer('missing', 7))->toBe(7)
        ->and($request->has('author.name'))->toBeTrue()
        ->and($request->filled('author.name'))->toBeTrue()
        ->and($request->filled('empty_string'))->toBeFalse()
        ->and($request->filled('empty_array'))->toBeFalse()
        ->and($request->missing('author.email'))->toBeTrue();
});

it('selects and excludes input values with dot notation', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/profile',
        ],
        input: [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret',
            'profile' => [
                'timezone' => 'UTC',
                'theme' => 'dark',
            ],
        ],
    );

    expect($request->only(['name', 'profile.timezone', 'missing']))->toBe([
        'name' => 'Ada',
        'profile' => [
            'timezone' => 'UTC',
        ],
    ])
        ->and($request->except(['password', 'profile.theme']))->toBe([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'profile' => [
                'timezone' => 'UTC',
            ],
        ]);
});

it('reads query values with typed accessors and dot notation', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/articles',
        ],
        query: [
            'page' => '2',
            'filters' => [
                'published' => '1',
                'rating' => '4.5',
                'tags' => ['php'],
            ],
            'empty' => '',
        ],
    );

    expect($request->queryValue('filters.tags'))->toBe(['php'])
        ->and($request->queryString('filters.published'))->toBe('1')
        ->and($request->queryInteger('page'))->toBe(2)
        ->and($request->queryFloat('filters.rating'))->toBe(4.5)
        ->and($request->queryBoolean('filters.published'))->toBeTrue()
        ->and($request->queryArray('filters.tags'))->toBe(['php'])
        ->and($request->queryHas('filters.published'))->toBeTrue()
        ->and($request->queryFilled('empty'))->toBeFalse()
        ->and($request->queryMissing('filters.author'))->toBeTrue()
        ->and($request->queryOnly(['page', 'filters.published']))->toBe([
            'page' => '2',
            'filters' => [
                'published' => '1',
            ],
        ])
        ->and($request->queryExcept(['filters.rating']))->toBe([
            'page' => '2',
            'filters' => [
                'published' => '1',
                'tags' => ['php'],
            ],
            'empty' => '',
        ]);
});

it('throws when Http request has no attached session', function (): void {
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/profile',
    ]);

    expect(fn() => $request->session())->toThrow(SessionNotAttachedException::class);
});
