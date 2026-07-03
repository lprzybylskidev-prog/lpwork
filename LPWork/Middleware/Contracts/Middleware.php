<?php

declare(strict_types=1);

namespace LPWork\Middleware\Contracts;

use Closure;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Defines the contract for middleware.
 */
interface Middleware
{
    /**
     * @param Closure(HttpRequest): HttpResponse $next
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse;
}
