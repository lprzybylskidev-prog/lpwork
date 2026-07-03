<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Context;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Routing\RouteAction;

/**
 * Registers route debug context provider services with the framework container.
 */
final readonly class RouteDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        $match = $context->routeMatch();

        if ($match === null) {
            return [
                'Route' => [
                    'Matched' => false,
                ],
            ];
        }

        $route = $match->route();
        $action = $route->action();

        return [
            'Route' => [
                'Matched' => true,
                'Name' => $route->name(),
                'Path' => $route->path(),
                'Methods' => $route->methods(),
                'Effective methods' => $route->effectiveMethods(),
                'Action' => $this->action($action),
                'Controller' => $action->isClosure() ? null : $action->controller(),
                'Controller method' => $action->isClosure() ? null : $action->method(),
                'API route' => $route->isApi(),
                'Parameters' => $match->parameters(),
                'Parameter rules' => $route->wheres(),
                'Declared middleware' => $route->middlewareList(),
            ],
        ];
    }

    private function action(RouteAction $action): string
    {
        if ($action->isClosure()) {
            return 'Closure';
        }

        return $action->controller() . '@' . $action->method();
    }
}
