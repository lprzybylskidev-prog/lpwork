<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli;

use LPWork\Bootstrap\Bootstrap;
use LPWork\Config\Config;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Renderers\CliExceptionRenderer;
use LPWork\Foundation\Application;
use LPWork\Responses\ConsoleResponse;
use Throwable;

/**
 * Represents the cli entrypoint framework component.
 */
final readonly class CliEntrypoint
{
    /**
     * @param list<string> $argv
     */
    public function __construct(
        private string $basePath,
        private array $argv,
        private ?Emitter $emitter = null,
    ) {}

    /**
     * Runs run.
     */
    public function run(): int
    {
        $app = null;

        try {
            $app = Bootstrap::initForConsole($this->basePath, $this->argv);
            $kernel = new CliKernel($app, $this->emitter);
            $kernel->bootstrap();

            return $kernel->handle($this->argv);
        } catch (Throwable $throwable) {
            return $this->emit($this->exceptionResponse($throwable, $app));
        }
    }

    private function exceptionResponse(Throwable $throwable, ?Application $app): ConsoleResponse
    {
        if ($app !== null) {
            try {
                $handler = $app->container()->make(CliExceptionHandler::class);

                if ($handler instanceof CliExceptionHandler) {
                    return ConsoleResponse::output(stderr: $handler->handle($throwable), exitCode: 1);
                }
            } catch (Throwable) {
                return $this->fallbackExceptionResponse($throwable);
            }
        }

        return $this->fallbackExceptionResponse($throwable);
    }

    private function fallbackExceptionResponse(Throwable $throwable): ConsoleResponse
    {
        return ConsoleResponse::output(
            stderr: new CliExceptionRenderer($this->appDebug())->render($throwable),
            exitCode: 1,
        );
    }

    private function appDebug(): bool
    {
        try {
            return Config::getBool('app.debug');
        } catch (Throwable) {
            return true;
        }
    }

    private function emit(ConsoleResponse $response): int
    {
        $emitter = $this->emitter ?? ConsoleEmitter::terminal();

        return $emitter->emit($response);
    }
}
