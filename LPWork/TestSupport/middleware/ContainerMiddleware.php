<?php

declare(strict_types=1);

namespace Tests\support\middleware;

use Closure;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

final readonly class ContainerMiddleware implements Middleware
{
    public function __construct(
        private InjectedHeader $header,
    ) {}

    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $response = $next($request);

        return new HttpResponse(
            body: $response->body() . '|container-middleware',
            statusCode: $response->statusCode(),
            headers: ['X-Container-Middleware' => $this->header->value(), ...$response->headers()],
        );
    }
}
