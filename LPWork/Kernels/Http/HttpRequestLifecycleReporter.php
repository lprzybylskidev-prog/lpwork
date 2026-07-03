<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\DebugBar\DebugBarResponseInjector;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Events\EventDispatcher;
use LPWork\Http\Contracts\HttpException;
use LPWork\Kernels\Http\Events\HttpRequestFailed;
use LPWork\Kernels\Http\Events\HttpRequestHandled;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;
use LPWork\Observability\RequestDiagnosticsResetter;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\RouteMatch;
use Throwable;

/**
 * Represents the http request lifecycle reporter framework component.
 */
final class HttpRequestLifecycleReporter
{
    private ?Throwable $handledThrowable = null;

    private bool $responseMetricRecorded = false;

    private bool $bootstrapMetricRecorded = false;

    private ?int $startedAt = null;

    private ?int $bootstrappedAt = null;

    /**
     * Creates a new HttpRequestLifecycleReporter instance.
     */
    public function __construct(
        private readonly ?HttpDebugContext $debugContext,
        private readonly ?DebugBarResponseInjector $debugBar,
        private readonly ?MetricCollector $metrics,
        private readonly ?RequestDiagnosticsResetter $diagnosticsResetter,
        private readonly ?EventDispatcher $events,
        private readonly int|float $applicationStartedAt,
    ) {}

    /**
     * Registers or stores bootstrapped.
     */
    public function bootstrapped(int $timestamp): void
    {
        $this->bootstrappedAt = $timestamp;
    }

    /**
     * Performs the begin operation.
     */
    public function begin(HttpRequest $request): int
    {
        $started = hrtime(true);
        $this->startedAt = $started;
        $this->handledThrowable = null;
        $this->responseMetricRecorded = false;
        $this->bootstrapMetricRecorded = false;
        $this->diagnosticsResetter?->reset();
        $this->debugContext?->reset();
        $this->debugContext?->setRequest($request);
        $this->recordBootstrapMetric();

        return $started;
    }

    /**
     * Performs the route matched operation.
     */
    public function routeMatched(RouteMatch $match): void
    {
        $this->debugContext?->setRouteMatch($match);
    }

    /**
     * @param list<Middleware> $middleware
     */
    public function middlewareResolved(array $middleware): void
    {
        $this->debugContext?->setMiddleware($middleware);
    }

    /**
     * Performs the controller request operation.
     */
    public function controllerRequest(HttpRequest $request, RouteMatch $match): void
    {
        $this->debugContext?->setRequest($request);
        $this->debugContext?->setRouteMatch($match);
    }

    /**
     * Builds or returns formatted exception.
     */
    public function formattedException(Throwable $throwable): void
    {
        $this->handledThrowable = $throwable;
        $this->recordExceptionMetric($throwable);
    }

    /**
     * Runs handled throwable.
     */
    public function handledThrowable(): ?Throwable
    {
        return $this->handledThrowable;
    }

    /**
     * Performs the record response operation.
     */
    public function recordResponse(HttpResponse $response, int $started): void
    {
        $this->debugContext?->setResponse($response);

        if ($this->responseMetricRecorded) {
            return;
        }

        $this->metrics?->report(new Metric(
            name: 'http.request.duration',
            value: $this->durationMs($started),
            unit: 'ms',
            tags: [
                'status' => $response->statusCode(),
                'route' => $this->routeName(),
                'exception' => null,
            ],
            recordedAtMs: $this->epochMsForHrtime($started),
        ));
        $this->responseMetricRecorded = true;
    }

    /**
     * Performs the inject debug bar operation.
     */
    public function injectDebugBar(HttpRequest $request, HttpResponse $response): HttpResponse
    {
        if ($this->debugBar === null) {
            return $response;
        }

        return $this->debugBar->inject($request, $response);
    }

    /**
     * Runs dispatch handled.
     */
    public function dispatchHandled(HttpRequest $request, HttpResponse $response, int $started): void
    {
        $this->events?->dispatch(new HttpRequestHandled(
            request: $request,
            response: $response,
            route: $this->routeName(),
            durationMs: $this->durationMs($started),
        ));
    }

    /**
     * Runs dispatch failed.
     */
    public function dispatchFailed(HttpRequest $request, HttpResponse $response, Throwable $throwable, int $started): void
    {
        $this->events?->dispatch(new HttpRequestFailed(
            request: $request,
            response: $response,
            route: $this->routeName(),
            durationMs: $this->durationMs($started),
            throwable: $throwable,
        ));
    }

    private function recordBootstrapMetric(): void
    {
        if ($this->bootstrapMetricRecorded || $this->bootstrappedAt === null) {
            return;
        }

        $this->metrics?->report(new Metric(
            name: 'application.bootstrap',
            value: $this->durationBetweenMs($this->applicationStartedAt, $this->bootstrappedAt),
            unit: 'ms',
            recordedAtMs: $this->epochMsForHrtime($this->applicationStartedAt),
        ));
        $this->bootstrapMetricRecorded = true;
    }

    private function recordExceptionMetric(Throwable $throwable): void
    {
        if ($this->responseMetricRecorded) {
            return;
        }

        $this->metrics?->report(new Metric(
            name: 'http.request.duration',
            value: $this->startedAt === null ? 0.0 : $this->durationMs($this->startedAt),
            unit: 'ms',
            tags: [
                'status' => $throwable instanceof HttpException ? $throwable->statusCode() : 500,
                'route' => $this->routeName(),
                'exception' => $throwable::class,
            ],
            recordedAtMs: $this->startedAt === null ? 0.0 : $this->epochMsForHrtime($this->startedAt),
        ));
        $this->responseMetricRecorded = true;
    }

    private function routeName(): ?string
    {
        return $this->debugContext?->routeMatch()?->route()->name();
    }

    private function durationMs(int $started): float
    {
        return round((hrtime(true) - $started) / 1_000_000, 3);
    }

    private function durationBetweenMs(int|float $started, int|float $ended): float
    {
        return round(max(0.0, $ended - $started) / 1_000_000, 3);
    }

    private function epochMsForHrtime(int|float $timestamp): float
    {
        $now = hrtime(true);

        return round((microtime(true) * 1000) - (($now - $timestamp) / 1_000_000), 3);
    }
}
