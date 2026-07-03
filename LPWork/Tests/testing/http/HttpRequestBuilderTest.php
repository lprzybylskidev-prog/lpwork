<?php

declare(strict_types=1);

use LPWork\Requests\UploadedFile;
use Tests\support\testing\Http\HttpRequestBuilder;

it('builds HTTP requests with safe defaults', function (): void {
    $request = HttpRequestBuilder::request('get', '/articles')->build();

    expect($request->method())->toBe('GET')
        ->and($request->uri())->toBe('/articles')
        ->and($request->path())->toBe('/articles')
        ->and($request->host())->toBe('localhost')
        ->and($request->scheme())->toBe('http')
        ->and($request->ip())->toBe('127.0.0.1');
});

it('merges URI query values with explicit query overrides', function (): void {
    $request = HttpRequestBuilder::request('GET', '/articles?page=1&filter=draft')
        ->withQuery(['page' => '2', 'sort' => 'new'])
        ->build();

    expect($request->uri())->toBe('/articles?page=2&filter=draft&sort=new')
        ->and($request->query())->toBe([
            'page' => '2',
            'filter' => 'draft',
            'sort' => 'new',
        ])
        ->and($request->fullUrl())->toBe('http://localhost/articles?page=2&filter=draft&sort=new');
});

it('maps headers body cookies files input and server metadata into a framework HTTP request', function (): void {
    $request = HttpRequestBuilder::request('POST', '/upload')
        ->withInput(['title' => 'Upload'])
        ->withCookies(['session' => 'abc'])
        ->withFiles([
            'avatar' => [
                'tmp_name' => '/tmp/avatar',
                'name' => 'avatar.png',
                'type' => 'image/png',
                'size' => 512,
                'error' => UPLOAD_ERR_OK,
            ],
        ])
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->withServer([
            'HTTP_HOST' => 'example.test',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '10.0.0.5',
        ])
        ->withBody('{"title":"Upload"}')
        ->build();

    expect($request->method())->toBe('POST')
        ->and($request->inputValue('title'))->toBe('Upload')
        ->and($request->cookie('session'))->toBe('abc')
        ->and($request->file('avatar'))->toBeInstanceOf(UploadedFile::class)
        ->and($request->header('Accept'))->toBe('application/json')
        ->and($request->header('Content-Type'))->toBe('application/json')
        ->and($request->header('Content-Length'))->toBe((string) strlen('{"title":"Upload"}'))
        ->and($request->header('X-Requested-With'))->toBe('XMLHttpRequest')
        ->and($request->host())->toBe('example.test')
        ->and($request->scheme())->toBe('https')
        ->and($request->ip())->toBe('10.0.0.5')
        ->and($request->body())->toBe('{"title":"Upload"}');
});

it('builds JSON request bodies with JSON headers', function (): void {
    $request = HttpRequestBuilder::request('POST', '/api/articles')
        ->withJsonBody(['title' => 'API article'])
        ->build();

    expect($request->body())->toBe('{"title":"API article"}')
        ->and($request->header('Accept'))->toBe('application/json')
        ->and($request->header('Content-Type'))->toBe('application/json')
        ->and($request->header('Content-Length'))->toBe((string) strlen('{"title":"API article"}'));
});
