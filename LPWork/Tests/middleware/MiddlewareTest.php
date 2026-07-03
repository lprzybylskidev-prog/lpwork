<?php

declare(strict_types=1);

use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

it('defines the Http middleware contract', function (): void {
    $middleware = new class implements Middleware {
        public function handle(HttpRequest $request, \Closure $next): HttpResponse
        {
            return $next($request);
        }
    };

    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/profile',
    ]);

    $response = $middleware->handle(
        $request,
        static fn(HttpRequest $request): HttpResponse => HttpResponse::text($request->path()),
    );

    expect($response->body())->toBe('/profile');
});
