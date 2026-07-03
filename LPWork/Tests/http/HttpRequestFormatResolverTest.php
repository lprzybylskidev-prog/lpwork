<?php

declare(strict_types=1);

use LPWork\Http\HttpRequestFormatResolver;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;

it('detects JSON request bodies from content type media types', function (): void {
    $resolver = new HttpRequestFormatResolver();

    $jsonRequest = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/articles',
            'CONTENT_TYPE' => 'application/vnd.api+json; charset=UTF-8',
        ],
        body: '{"title":"Hello"}',
    );

    $formRequest = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/articles',
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    ]);

    expect($resolver->hasJsonBody($jsonRequest))->toBeTrue()
        ->and($resolver->hasJsonBody($formRequest))->toBeFalse();
});

it('resolves the expected HTTP response format from accept headers and API context', function (): void {
    $resolver = new HttpRequestFormatResolver();

    $htmlRequest = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
    ]);

    $jsonRequest = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'application/vnd.api+json; version=1',
    ]);

    expect($resolver->expectsJson($htmlRequest))->toBeFalse()
        ->and($resolver->responseFormat($htmlRequest))->toBe(ResponseFormat::Html)
        ->and($resolver->expectsJson($jsonRequest))->toBeTrue()
        ->and($resolver->responseFormat($jsonRequest))->toBe(ResponseFormat::Json)
        ->and($resolver->expectsJson($htmlRequest, api: true))->toBeTrue()
        ->and($resolver->responseFormat($htmlRequest, api: true))->toBe(ResponseFormat::Json);
});

it('prefers the best accepted response format by quality', function (): void {
    $resolver = new HttpRequestFormatResolver();

    $htmlPreferred = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'application/json;q=0.2, text/html;q=0.9',
    ]);

    $jsonPreferred = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'text/html;q=0.1, application/vnd.api+json;q=0.8',
    ]);

    expect($resolver->responseFormat($htmlPreferred))->toBe(ResponseFormat::Html)
        ->and($resolver->responseFormat($jsonPreferred))->toBe(ResponseFormat::Json);
});

it('keeps wildcard web requests HTML unless the request is AJAX or API', function (): void {
    $resolver = new HttpRequestFormatResolver();

    $wildcard = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => '*/*',
    ]);

    $ajax = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => '*/*',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    ]);

    expect($resolver->responseFormat($wildcard))->toBe(ResponseFormat::Html)
        ->and($resolver->responseFormat($wildcard, api: true))->toBe(ResponseFormat::Json)
        ->and($resolver->responseFormat($ajax))->toBe(ResponseFormat::Json);
});
