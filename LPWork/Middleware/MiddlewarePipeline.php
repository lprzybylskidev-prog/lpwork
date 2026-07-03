<?php

declare(strict_types=1);

namespace LPWork\Middleware;

use Closure;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Represents the middleware pipeline framework component.
 */
final readonly class MiddlewarePipeline
{
    /**
     * @param list<Middleware> $middleware
     */
    public function __construct(
        private array $middleware = [],
    ) {}

    /**
     * @param Closure(HttpRequest): HttpResponse $destination
     */
    public function handle(HttpRequest $request, Closure $destination): HttpResponse
    {
        $next = $destination;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn(HttpRequest $request): HttpResponse => $middleware->handle($request, $next);
        }

        return $next($request);
    }
}
