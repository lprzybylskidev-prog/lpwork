<?php

declare(strict_types=1);

namespace Tests\support\routing;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use RuntimeException;
use Tests\support\validation\StorePostFormRequest;

final class TestController
{
    public function index(HttpRequest $request): HttpResponse
    {
        return HttpResponse::text(sprintf('%s %s', $request->method(), $request->path()));
    }

    public function create(): void {}

    public function store(): void {}

    public function apiStore(HttpRequest $request): HttpResponse
    {
        return HttpResponse::json([
            'title' => $request->inputValue('title'),
            'session_attached' => false,
        ], statusCode: 201);
    }

    public function inputTitle(HttpRequest $request): HttpResponse
    {
        $title = $request->inputValue('title', 'missing');

        return HttpResponse::text(is_scalar($title) ? (string) $title : 'missing');
    }

    public function show(HttpRequest $request, string $id): HttpResponse
    {
        return HttpResponse::text(sprintf('%s %s %s', $request->method(), $request->path(), $id));
    }

    public function validatedStore(StorePostFormRequest $request, string $id): HttpResponse
    {
        return HttpResponse::text(sprintf(
            '%s %s %s %s',
            $request->method(),
            $request->path(),
            $id,
            $request->string('title'),
        ));
    }

    public function invalidFormRequestShouldNotRun(StorePostFormRequest $request): HttpResponse
    {
        throw new RuntimeException('Controller action was executed before validation failed.');
    }

    public function edit(): void {}

    public function update(HttpRequest $request, string $id): HttpResponse
    {
        return HttpResponse::text(sprintf('%s %s %s', $request->method(), $request->path(), $id));
    }

    public function destroy(): void {}

    public function invalidResponse(): string
    {
        return 'invalid';
    }
}
