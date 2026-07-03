<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\DebugBar\DebugBarResponseInjector;
use LPWork\DebugDump\DebugDumper;
use LPWork\DebugDump\DebugDumpExceptionResponseFactory;
use LPWork\DebugDump\DebugDumpResponseInjector;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\Emitters\HttpEmitter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\ErrorHandling\Renderers\JsonHttpExceptionRenderer;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\Application;
use LPWork\Http\HttpRequestFormatResolver;
use LPWork\Kernels\AbstractKernel;
use LPWork\Kernels\Http\Contracts\HttpKernel as ContractsHttpKernel;
use LPWork\Maintenance\MaintenanceMiddleware;
use LPWork\Observability\MetricCollector;
use LPWork\Observability\RequestDiagnosticsResetter;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
use LPWork\Security\Http\HttpSecurityMiddleware;
use Throwable;

/**
 * Represents the http kernel framework component.
 */
final class HttpKernel extends AbstractKernel implements ContractsHttpKernel
{
    private HttpExceptionResponder $exceptionResponder;

    private readonly Router $router;

    private readonly bool $hasCustomRouter;

    private readonly RouteMatcher $routeMatcher;

    private readonly MiddlewareResolver $middlewareResolver;

    private readonly HttpThrottleMiddlewareResolver $httpThrottleMiddlewareResolver;

    private readonly WebSecurityMiddlewareResolver $webSecurityMiddlewareResolver;

    private readonly ControllerDispatcher $controllerDispatcher;

    private readonly JsonRequestBodyParser $jsonRequestBodyParser;

    private readonly HttpRequestFormatResolver $requestFormatResolver;

    private readonly HttpResponseFormatResolver $responseFormatResolver;

    private readonly HttpExceptionResponseFactory $exceptionResponseFactory;

    private readonly HttpRequestLifecycleReporter $reporter;

    private readonly HttpRequestExecutor $executor;

    private readonly ?DebugDumper $debugDumper;

    private readonly ?DebugDumpResponseInjector $debugDumpInjector;

    /**
     * Creates a new HttpKernel instance.
     */
    public function __construct(
        Application $app,
        private readonly ?Emitter $emitter = null,
        ?Router $router = null,
        int|float|null $applicationStartedAt = null,
    ) {
        parent::__construct($app);

        $this->hasCustomRouter = $router !== null;
        $this->router = $router ?? $this->resolveRouter();
        $this->routeMatcher = $this->resolveRouteMatcher();
        $this->middlewareResolver = $this->resolveMiddlewareResolver();
        $this->httpThrottleMiddlewareResolver = $this->resolveHttpThrottleMiddlewareResolver();
        $this->webSecurityMiddlewareResolver = $this->resolveWebSecurityMiddlewareResolver();
        $this->controllerDispatcher = $this->resolveControllerDispatcher();
        $this->requestFormatResolver = new HttpRequestFormatResolver();
        $this->jsonRequestBodyParser = new JsonRequestBodyParser($this->requestFormatResolver);
        $this->responseFormatResolver = new HttpResponseFormatResolver($this->requestFormatResolver);
        $this->exceptionResponseFactory = new HttpExceptionResponseFactory(
            $this->htmlHttpExceptionRenderer(),
            $this->jsonHttpExceptionRenderer(),
            debugDumpResponses: $this->resolveDebugDumpExceptionResponses(),
        );
        $this->exceptionResponder = new HttpExceptionResponder(null, $this->httpProductionExceptionRenderer());
        $this->reporter = new HttpRequestLifecycleReporter(
            $this->resolveDebugContext(),
            $this->resolveDebugBar(),
            $this->resolveMetrics(),
            $this->resolveDiagnosticsResetter(),
            $this->resolveEventDispatcher(),
            $applicationStartedAt ?? hrtime(true),
        );
        $this->executor = new HttpRequestExecutor(
            router: $this->router,
            routeMatcher: $this->routeMatcher,
            middlewareResolver: $this->middlewareResolver,
            httpThrottleMiddlewareResolver: $this->httpThrottleMiddlewareResolver,
            webSecurityMiddlewareResolver: $this->webSecurityMiddlewareResolver,
            controllerDispatcher: $this->controllerDispatcher,
            jsonRequestBodyParser: $this->jsonRequestBodyParser,
            responseFormatResolver: $this->responseFormatResolver,
            exceptionResponseFactory: $this->exceptionResponseFactory,
            reporter: $this->reporter,
            httpSecurityMiddleware: $this->resolveHttpSecurityMiddleware(),
            maintenanceMiddleware: $this->resolveMaintenanceMiddleware(),
        );
        $this->debugDumper = $this->resolveDebugDumper();
        $this->debugDumpInjector = $this->resolveDebugDumpInjector();
    }

    /**
     * Registers or stores bootstrap.
     */
    public function bootstrap(): void
    {
        $this->registerErrorHandler();
        $this->exceptionResponder = new HttpExceptionResponder(
            $this->httpExceptionHandler(),
            $this->httpProductionExceptionRenderer(),
        );
        $this->reporter->bootstrapped(hrtime(true));
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(?HttpRequest $request = null): int
    {
        $emitter = $this->emitter ?? $this->httpEmitter();
        $request ??= HttpRequest::fromGlobals();
        $this->debugDumper?->reset();
        $started = $this->reporter->begin($request);

        try {
            $response = $this->executor->response($request);
            $this->reporter->recordResponse($response, $started);
            $response = $this->reporter->injectDebugBar($request, $response);
            $response = $this->injectDebugDump($response);
            $exitCode = $emitter->emit($response);
            $handledThrowable = $this->reporter->handledThrowable();

            if ($handledThrowable !== null) {
                $this->reporter->dispatchFailed($request, $response, $handledThrowable, $started);
            } else {
                $this->reporter->dispatchHandled($request, $response, $started);
            }

            return $exitCode;
        } catch (Throwable $throwable) {
            $response = $this->exceptionResponse($throwable);
            $this->reporter->recordResponse($response, $started);
            $response = $this->reporter->injectDebugBar($request, $response);
            $response = $this->injectDebugDump($response);
            $exitCode = $emitter->emit($response);
            $this->reporter->dispatchFailed($request, $response, $throwable, $started);

            return $exitCode;
        }
    }

    private function resolveRouter(): Router
    {
        $router = $this->containerObject(Router::class);

        if ($router instanceof Router) {
            return $router;
        }

        return new Router();
    }

    private function exceptionResponse(Throwable $throwable): HttpResponse
    {
        return $this->exceptionResponder->respond($throwable);
    }

    private function httpEmitter(): HttpEmitter
    {
        $emitter = $this->containerObject(HttpEmitter::class);

        if ($emitter instanceof HttpEmitter) {
            return $emitter;
        }

        return HttpEmitter::browser();
    }

    private function resolveRouteMatcher(): RouteMatcher
    {
        if ($this->hasCustomRouter) {
            return new RouteMatcher($this->router);
        }

        $matcher = $this->containerObject(RouteMatcher::class);

        if ($matcher instanceof RouteMatcher) {
            return $matcher;
        }

        return new RouteMatcher($this->router);
    }

    private function resolveMiddlewareResolver(): MiddlewareResolver
    {
        $resolver = $this->containerObject(MiddlewareResolver::class);

        if ($resolver instanceof MiddlewareResolver) {
            return $resolver;
        }

        return new MiddlewareResolver($this->app);
    }

    private function resolveHttpThrottleMiddlewareResolver(): HttpThrottleMiddlewareResolver
    {
        $resolver = $this->containerObject(HttpThrottleMiddlewareResolver::class);

        if ($resolver instanceof HttpThrottleMiddlewareResolver) {
            return $resolver;
        }

        return new HttpThrottleMiddlewareResolver($this->app);
    }

    private function resolveWebSecurityMiddlewareResolver(): WebSecurityMiddlewareResolver
    {
        $resolver = $this->containerObject(WebSecurityMiddlewareResolver::class);

        if ($resolver instanceof WebSecurityMiddlewareResolver) {
            return $resolver;
        }

        return new WebSecurityMiddlewareResolver($this->app);
    }

    private function resolveControllerDispatcher(): ControllerDispatcher
    {
        $dispatcher = $this->containerObject(ControllerDispatcher::class);

        if ($dispatcher instanceof ControllerDispatcher) {
            return $dispatcher;
        }

        return new ControllerDispatcher($this->app);
    }

    private function resolveEventDispatcher(): ?EventDispatcher
    {
        $dispatcher = $this->containerObject(EventDispatcher::class);

        return $dispatcher instanceof EventDispatcher ? $dispatcher : null;
    }

    private function resolveHttpSecurityMiddleware(): ?HttpSecurityMiddleware
    {
        $middleware = $this->containerObject(HttpSecurityMiddleware::class);

        if ($middleware instanceof HttpSecurityMiddleware) {
            return $middleware;
        }

        return null;
    }

    private function resolveMaintenanceMiddleware(): ?MaintenanceMiddleware
    {
        $middleware = $this->containerObject(MaintenanceMiddleware::class);

        if ($middleware instanceof MaintenanceMiddleware) {
            return $middleware;
        }

        return null;
    }

    private function resolveDebugContext(): ?HttpDebugContext
    {
        $context = $this->containerObject(HttpDebugContext::class);

        if ($context instanceof HttpDebugContext) {
            return $context;
        }

        return null;
    }

    private function resolveDebugBar(): ?DebugBarResponseInjector
    {
        $debugBar = $this->containerObject(DebugBarResponseInjector::class);

        if ($debugBar instanceof DebugBarResponseInjector) {
            return $debugBar;
        }

        return null;
    }

    private function resolveDebugDumper(): ?DebugDumper
    {
        $dumper = $this->containerObject(DebugDumper::class);

        if ($dumper instanceof DebugDumper) {
            return $dumper;
        }

        return null;
    }

    private function resolveDebugDumpInjector(): ?DebugDumpResponseInjector
    {
        $injector = $this->containerObject(DebugDumpResponseInjector::class);

        if ($injector instanceof DebugDumpResponseInjector) {
            return $injector;
        }

        return null;
    }

    private function resolveDebugDumpExceptionResponses(): ?DebugDumpExceptionResponseFactory
    {
        $factory = $this->containerObject(DebugDumpExceptionResponseFactory::class);

        if ($factory instanceof DebugDumpExceptionResponseFactory) {
            return $factory;
        }

        return null;
    }

    private function injectDebugDump(HttpResponse $response): HttpResponse
    {
        if ($this->debugDumpInjector === null) {
            return $response;
        }

        return $this->debugDumpInjector->inject($response);
    }

    private function resolveMetrics(): ?MetricCollector
    {
        $metrics = $this->containerObject(MetricCollector::class);

        if ($metrics instanceof MetricCollector) {
            return $metrics;
        }

        return null;
    }

    private function resolveDiagnosticsResetter(): ?RequestDiagnosticsResetter
    {
        $resetter = $this->containerObject(RequestDiagnosticsResetter::class);

        if ($resetter instanceof RequestDiagnosticsResetter) {
            return $resetter;
        }

        return null;
    }

    private function httpProductionExceptionRenderer(): HttpProductionExceptionRenderer
    {
        $renderer = $this->containerObject(HttpProductionExceptionRenderer::class);

        if ($renderer instanceof HttpProductionExceptionRenderer) {
            return $renderer;
        }

        return new HttpProductionExceptionRenderer();
    }

    private function htmlHttpExceptionRenderer(): HttpExceptionRenderer
    {
        $renderer = $this->containerObject(HttpExceptionRenderer::class);

        if ($renderer instanceof HttpExceptionRenderer) {
            return $renderer;
        }

        return $this->httpProductionExceptionRenderer();
    }

    private function jsonHttpExceptionRenderer(): JsonHttpExceptionRenderer
    {
        $renderer = $this->containerObject(JsonHttpExceptionRenderer::class);

        if ($renderer instanceof JsonHttpExceptionRenderer) {
            return $renderer;
        }

        return new JsonHttpExceptionRenderer();
    }

}
