<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Http\MethodSpoofing;
use LPWork\Requests\HttpRequest;
use LPWork\Routing\RouteMatch;
use LPWork\Routing\Router;

/**
 * Represents the route matcher framework component.
 */
final readonly class RouteMatcher
{
    /**
     * Creates a new RouteMatcher instance.
     */
    public function __construct(
        private Router $router,
    ) {}

    public function match(HttpRequest $request): RouteMatch
    {
        return $this->router->routes()->match(
            MethodSpoofing::resolve($request->method(), $request->input()),
            $request->path(),
        );
    }
}
