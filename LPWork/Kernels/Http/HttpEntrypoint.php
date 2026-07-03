<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Bootstrap\Bootstrap;
use LPWork\Config\Config;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\Emitters\HttpEmitter;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Renderers\HttpDebugExceptionRenderer;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\Foundation\Application;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Represents the http entrypoint framework component.
 */
final readonly class HttpEntrypoint
{
    /**
     * Creates a new HttpEntrypoint instance.
     */
    public function __construct(
        private string $basePath,
        private ?Emitter $emitter = null,
    ) {}

    /**
     * Runs run.
     */
    public function run(): int
    {
        $started = hrtime(true);
        $app = null;

        try {
            $app = Bootstrap::init($this->basePath);
            $kernel = new HttpKernel($app, $this->emitter, applicationStartedAt: $started);
            $kernel->bootstrap();

            return $kernel->handle();
        } catch (Throwable $throwable) {
            return $this->emit($this->exceptionResponse($throwable, $app));
        }
    }

    private function exceptionResponse(Throwable $throwable, ?Application $app): HttpResponse
    {
        if ($app !== null) {
            try {
                $handler = $app->container()->make(HttpExceptionHandler::class);

                if ($handler instanceof HttpExceptionHandler) {
                    return $handler->handle($throwable);
                }
            } catch (Throwable) {
                return $this->fallbackExceptionResponse($throwable);
            }
        }

        return $this->fallbackExceptionResponse($throwable);
    }

    private function fallbackExceptionResponse(Throwable $throwable): HttpResponse
    {
        if ($this->appDebug()) {
            return new HttpDebugExceptionRenderer($this->basePath)->render($throwable);
        }

        return new HttpProductionExceptionRenderer()->render($throwable);
    }

    private function appDebug(): bool
    {
        try {
            return Config::getBool('app.debug');
        } catch (Throwable) {
            return false;
        }
    }

    private function emit(HttpResponse $response): int
    {
        $emitter = $this->emitter ?? HttpEmitter::browser();

        return $emitter->emit($response);
    }
}
