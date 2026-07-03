<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Maintenance\MaintenanceMiddleware;
use LPWork\Middleware\MiddlewarePipeline;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\RouteMatch;
use LPWork\Routing\Router;
use LPWork\Security\Http\HttpSecurityMiddleware;
use Throwable;

/**
 * Represents the http request executor framework component.
 */
final readonly class HttpRequestExecutor
{
    /**
     * Creates a new HttpRequestExecutor instance.
     */
    public function __construct(
        private Router $router,
        private RouteMatcher $routeMatcher,
        private MiddlewareResolver $middlewareResolver,
        private HttpThrottleMiddlewareResolver $httpThrottleMiddlewareResolver,
        private WebSecurityMiddlewareResolver $webSecurityMiddlewareResolver,
        private ControllerDispatcher $controllerDispatcher,
        private JsonRequestBodyParser $jsonRequestBodyParser,
        private HttpResponseFormatResolver $responseFormatResolver,
        private HttpExceptionResponseFactory $exceptionResponseFactory,
        private HttpRequestLifecycleReporter $reporter,
        private ?HttpSecurityMiddleware $httpSecurityMiddleware,
        private ?MaintenanceMiddleware $maintenanceMiddleware,
    ) {}

    /**
     * Performs the response operation.
     */
    public function response(HttpRequest $request): HttpResponse
    {
        if ($this->maintenanceMiddleware !== null) {
            return $this->maintenanceMiddleware->handle(
                $request,
                fn(HttpRequest $request): HttpResponse => $this->securedResponse($request),
            );
        }

        return $this->securedResponse($request);
    }

    private function securedResponse(HttpRequest $request): HttpResponse
    {
        if ($this->httpSecurityMiddleware === null) {
            return $this->routedResponse($request);
        }

        try {
            return $this->httpSecurityMiddleware->handle(
                $request,
                fn(HttpRequest $request): HttpResponse => $this->routedResponse($request),
            );
        } catch (Throwable $throwable) {
            return $this->formattedExceptionResponse($throwable, $this->responseFormatResolver->resolve($request), $request);
        }
    }

    private function routedResponse(HttpRequest $request): HttpResponse
    {
        try {
            $match = $this->routeMatcher->match($request);
            $this->reporter->routeMatched($match);
        } catch (Throwable $throwable) {
            return $this->formattedExceptionResponse($throwable, $this->responseFormatResolver->resolve($request), $request);
        }

        $format = $this->responseFormatResolver->resolve($request, $match);

        if ($match->route()->isApi()) {
            try {
                return $this->matchedResponse($this->jsonRequestBodyParser->parse($request), $match, $format);
            } catch (Throwable $throwable) {
                return $this->formattedExceptionResponse($throwable, $format, $request);
            }
        }

        try {
            return $this->matchedResponse($request, $match, $format);
        } catch (Throwable $throwable) {
            return $this->formattedExceptionResponse($throwable, $format, $request);
        }
    }

    private function matchedResponse(HttpRequest $request, RouteMatch $match, ResponseFormat $format): HttpResponse
    {
        $middleware = [
            ...$this->httpThrottleMiddlewareResolver->resolve($match),
            ...$this->webSecurityMiddlewareResolver->resolve($match),
            ...$this->middlewareResolver->resolveGlobal($this->router),
            ...$this->middlewareResolver->resolve($match),
        ];
        $this->reporter->middlewareResolved($middleware);

        return new MiddlewarePipeline($middleware)
            ->handle(
                $request,
                function (HttpRequest $request) use ($match, $format): HttpResponse {
                    $this->reporter->controllerRequest($request, $match);

                    try {
                        return $this->controllerDispatcher->dispatch($request, $match);
                    } catch (Throwable $throwable) {
                        return $this->formattedExceptionResponse($throwable, $format, $request);
                    }
                },
            );
    }

    private function formattedExceptionResponse(Throwable $throwable, ResponseFormat $format, ?HttpRequest $request = null): HttpResponse
    {
        $this->reporter->formattedException($throwable);

        return $this->exceptionResponseFactory->make($throwable, $format, $request);
    }
}
