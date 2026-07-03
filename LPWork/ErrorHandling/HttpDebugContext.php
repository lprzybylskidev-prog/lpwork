<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\RouteMatch;
use Throwable;

/**
 * Represents the http debug context framework component.
 */
final class HttpDebugContext
{
    private ?HttpRequest $request = null;

    private ?RouteMatch $routeMatch = null;

    private ?Throwable $throwable = null;

    private ?HttpResponse $response = null;

    /**
     * @var list<string>
     */
    private array $middleware = [];

    /**
     * @var list<HttpDebugContextProvider>
     */
    private array $providers = [];

    /**
     * Registers or stores set request.
     */
    public function setRequest(HttpRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * Registers or stores set route match.
     */
    public function setRouteMatch(RouteMatch $routeMatch): void
    {
        $this->routeMatch = $routeMatch;
    }

    /**
     * Registers or stores set throwable.
     */
    public function setThrowable(Throwable $throwable): void
    {
        $this->throwable = $throwable;
    }

    /**
     * Registers or stores set response.
     */
    public function setResponse(HttpResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @param list<Middleware> $middleware
     */
    public function setMiddleware(array $middleware): void
    {
        $this->middleware = array_map(static fn(Middleware $middleware): string => $middleware::class, $middleware);
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->request = null;
        $this->routeMatch = null;
        $this->throwable = null;
        $this->response = null;
        $this->middleware = [];
    }

    /**
     * Performs the request operation.
     */
    public function request(): ?HttpRequest
    {
        return $this->request;
    }

    /**
     * Performs the route match operation.
     */
    public function routeMatch(): ?RouteMatch
    {
        return $this->routeMatch;
    }

    /**
     * Performs the throwable operation.
     */
    public function throwable(): ?Throwable
    {
        return $this->throwable;
    }

    /**
     * Performs the response operation.
     */
    public function response(): ?HttpResponse
    {
        return $this->response;
    }

    /**
     * @return list<string>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * Registers or stores add provider.
     */
    public function addProvider(HttpDebugContextProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $data = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->context($this) as $group => $properties) {
                $data[$group] = $properties;
            }
        }

        return $data;
    }
}
