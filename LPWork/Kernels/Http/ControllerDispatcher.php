<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Foundation\Application;
use LPWork\Kernels\Http\Exceptions\FormRequestFactoryNotRegisteredException;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Exceptions\ControllerMethodNotFoundException;
use LPWork\Routing\Exceptions\ControllerNotFoundException;
use LPWork\Routing\Exceptions\InvalidRouteResponseException;
use LPWork\Routing\RouteMatch;
use LPWork\Validation\FormRequestFactory;

/**
 * Represents the controller dispatcher framework component.
 */
final readonly class ControllerDispatcher
{
    /**
     * Creates a new ControllerDispatcher instance.
     */
    public function __construct(
        private Application $app,
        private ?ClosureRouteDispatcher $closureDispatcher = null,
        private ?RouteActionParametersResolver $parameters = null,
    ) {}

    /**
     * Runs dispatch.
     */
    public function dispatch(HttpRequest $request, RouteMatch $match): HttpResponse
    {
        $action = $match->route()->action();

        if ($action->isClosure()) {
            $callable = $action->closure();

            if ($callable === null) {
                throw InvalidRouteResponseException::forClosure($match->route()->path());
            }

            return $this->closureDispatcher()->dispatch($callable, $request, $match);
        }

        $controller = $action->controller();
        $method = $action->method();

        if (!class_exists($controller)) {
            throw new ControllerNotFoundException($controller);
        }

        $controllerInstance = $this->app->container()->make($controller);
        $callable = [$controllerInstance, $method];

        if (!is_callable($callable)) {
            throw new ControllerMethodNotFoundException($controller, $method);
        }

        $response = $this->app->container()->call($callable, $this->parameters()->resolve(
            callback: $callable,
            request: $request,
            match: $match,
        ));

        if (!$response instanceof HttpResponse) {
            throw new InvalidRouteResponseException($controller, $method);
        }

        return $response;
    }

    private function closureDispatcher(): ClosureRouteDispatcher
    {
        return $this->closureDispatcher ?? new ClosureRouteDispatcher($this->app, $this->parameters());
    }

    private function parameters(): RouteActionParametersResolver
    {
        return $this->parameters ?? new RouteActionParametersResolver(fn(): FormRequestFactory => $this->formRequests());
    }

    private function formRequests(): FormRequestFactory
    {
        $formRequests = $this->app->container()->make(FormRequestFactory::class);

        if (!$formRequests instanceof FormRequestFactory) {
            throw new FormRequestFactoryNotRegisteredException();
        }

        return $formRequests;
    }
}
