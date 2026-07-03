<?php

declare(strict_types=1);

namespace LPWork\Maintenance;

use Closure;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Applies maintenance middleware middleware behavior.
 */
final readonly class MaintenanceMiddleware implements Middleware
{
    /**
     * Creates a new MaintenanceMiddleware instance.
     */
    public function __construct(
        private MaintenanceMode $maintenance,
        private MaintenancePageRenderer $renderer,
    ) {}

    /**
     * @param Closure(HttpRequest): HttpResponse $next
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $state = $this->maintenance->state();

        if ($state->isActive()) {
            return $this->renderer->render($state);
        }

        return $next($request);
    }
}
