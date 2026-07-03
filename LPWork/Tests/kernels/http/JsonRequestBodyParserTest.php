<?php

declare(strict_types=1);

use LPWork\Kernels\Http\Exceptions\MalformedJsonRequestBodyException;
use LPWork\Kernels\Http\JsonRequestBodyParser;
use LPWork\Requests\HttpRequest;

it('parses JSON object request bodies into request input', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/articles',
            'CONTENT_TYPE' => 'application/json',
        ],
        body: '{"title":"Draft","published":true}',
    );

    $parsed = new JsonRequestBodyParser()->parse($request);

    expect($parsed->input())->toBe([
        'title' => 'Draft',
        'published' => true,
    ]);
});

it('leaves non JSON and empty JSON request bodies unchanged', function (): void {
    $plain = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/articles',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ],
        input: ['title' => 'Form'],
        body: 'title=Ignored',
    );
    $emptyJson = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/articles',
            'CONTENT_TYPE' => 'application/json',
        ],
        input: ['title' => 'Existing'],
        body: '   ',
    );
    $parser = new JsonRequestBodyParser();

    expect($parser->parse($plain))->toBe($plain)
        ->and($parser->parse($emptyJson))->toBe($emptyJson);
});

it('rejects malformed and non object JSON request bodies', function (): void {
    $parser = new JsonRequestBodyParser();

    expect(fn(): HttpRequest => $parser->parse(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/articles',
            'CONTENT_TYPE' => 'application/json',
        ],
        body: '{"title":',
    )))
        ->toThrow(MalformedJsonRequestBodyException::class, 'The HTTP JSON request body is malformed.')
        ->and(fn(): HttpRequest => $parser->parse(HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/api/articles',
                'CONTENT_TYPE' => 'application/json',
            ],
            body: '["not", "an", "object"]',
        )))
        ->toThrow(MalformedJsonRequestBodyException::class, 'The HTTP JSON request body must be an object.');
});
