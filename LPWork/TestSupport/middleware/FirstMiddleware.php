<?php

declare(strict_types=1);

namespace Tests\support\middleware;

use Closure;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

final class FirstMiddleware implements Middleware
{
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $response = $next($request);

        return new HttpResponse(
            body: $response->body() . '|first',
            statusCode: $response->statusCode(),
            headers: ['X-First-Middleware' => 'passed', ...$response->headers()],
        );
    }
}
