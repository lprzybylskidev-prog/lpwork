<?php

declare(strict_types=1);

namespace Tests\support\middleware;

use Closure;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

final class SecondMiddleware implements Middleware
{
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $response = $next($request);

        return new HttpResponse(
            body: $response->body() . '|second',
            statusCode: $response->statusCode(),
            headers: ['X-Second-Middleware' => 'passed', ...$response->headers()],
        );
    }
}
