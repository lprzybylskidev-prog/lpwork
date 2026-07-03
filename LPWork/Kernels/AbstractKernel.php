<?php

declare(strict_types=1);

namespace LPWork\Kernels;

use LPWork\Config\Config;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Contracts\ExceptionRenderer;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\ErrorHandler;
use LPWork\ErrorHandling\ExceptionReporterFactory;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Renderers\CliExceptionRenderer;
use LPWork\ErrorHandling\Renderers\HttpDebugExceptionRenderer;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\Foundation\Application;
use LPWork\Observability\DiagnosticsSnapshotFactory;

/**
 * Represents the abstract kernel framework component.
 */
abstract class AbstractKernel
{
    /**
     * Creates a new AbstractKernel instance.
     */
    public function __construct(
        protected readonly Application $app,
    ) {}

    protected function registerErrorHandler(): void
    {
        $errorHandler = $this->containerObject(ErrorHandler::class);
        $errorHandler = $errorHandler instanceof ErrorHandler ? $errorHandler : new ErrorHandler();

        $errorHandler->register();
    }

    protected function cliExceptionHandler(): CliExceptionHandler
    {
        $handler = $this->containerObject(CliExceptionHandler::class);

        if ($handler instanceof CliExceptionHandler) {
            return $handler;
        }

        return new CliExceptionHandler(
            $this->exceptionReporter(),
            $this->cliExceptionRenderer(),
        );
    }

    protected function httpExceptionHandler(): HttpExceptionHandler
    {
        $handler = $this->containerObject(HttpExceptionHandler::class);

        if ($handler instanceof HttpExceptionHandler) {
            return $handler;
        }

        return new HttpExceptionHandler(
            $this->exceptionReporter(),
            $this->httpExceptionRenderer(),
        );
    }

    protected function cliExceptionRenderer(): ExceptionRenderer
    {
        $renderer = $this->containerObject(CliExceptionRenderer::class);

        if ($renderer instanceof ExceptionRenderer) {
            return $renderer;
        }

        return new CliExceptionRenderer();
    }

    protected function httpExceptionRenderer(): HttpExceptionRenderer
    {
        $renderer = $this->containerObject(HttpExceptionRenderer::class);

        if ($renderer instanceof HttpExceptionRenderer) {
            return $renderer;
        }

        if (Config::getBool('app.debug')) {
            $renderer = $this->containerObject(HttpDebugExceptionRenderer::class);

            if ($renderer instanceof HttpExceptionRenderer) {
                return $renderer;
            }

            $context = $this->containerObject(HttpDebugContext::class);
            $snapshots = $this->containerObject(DiagnosticsSnapshotFactory::class);
            $debugBar = $this->containerObject(DebugBarPageRenderer::class);

            return new HttpDebugExceptionRenderer(
                $this->app->basePath(),
                $context instanceof HttpDebugContext ? $context : null,
                $snapshots instanceof DiagnosticsSnapshotFactory ? $snapshots : null,
                debugBar: $debugBar instanceof DebugBarPageRenderer ? $debugBar : null,
            );
        }

        $renderer = $this->containerObject(HttpProductionExceptionRenderer::class);

        if ($renderer instanceof HttpExceptionRenderer) {
            return $renderer;
        }

        return new HttpProductionExceptionRenderer();
    }

    protected function exceptionReporter(): ExceptionReporter
    {
        $reporter = $this->containerObject(ExceptionReporter::class);

        if ($reporter instanceof ExceptionReporter) {
            return $reporter;
        }

        return ExceptionReporterFactory::logging($this->app->basePath());
    }

    /**
     * @param class-string $class
     */
    protected function containerObject(string $class): ?object
    {
        try {
            return $this->app->container()->make($class);
        } catch (CannotResolveDependencyException) {
            return null;
        }
    }
}
