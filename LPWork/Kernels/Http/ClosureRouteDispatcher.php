<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use Closure;
use LPWork\Foundation\Application;
use LPWork\Kernels\Http\Exceptions\FormRequestFactoryNotRegisteredException;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Exceptions\InvalidRouteResponseException;
use LPWork\Routing\RouteMatch;
use LPWork\Validation\FormRequestFactory;

/**
 * Represents the closure route dispatcher framework component.
 */
final readonly class ClosureRouteDispatcher
{
    /**
     * Creates a new ClosureRouteDispatcher instance.
     */
    public function __construct(
        private Application $app,
        private ?RouteActionParametersResolver $parameters = null,
    ) {}

    /**
     * Runs dispatch.
     */
    public function dispatch(Closure $closure, HttpRequest $request, RouteMatch $match): HttpResponse
    {
        $response = $this->app->container()->call($closure, $this->parameters()->resolve(
            callback: $closure,
            request: $request,
            match: $match,
            failOnMissingRouteValue: true,
        ));

        if (!$response instanceof HttpResponse) {
            throw InvalidRouteResponseException::forClosure($match->route()->path());
        }

        return $response;
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
