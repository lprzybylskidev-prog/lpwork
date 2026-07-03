<?php

declare(strict_types=1);

namespace LPWork\Console;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\ConsoleMiddleware;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Foundation\Application;
use LPWork\Middleware\Exceptions\InvalidMiddlewareException;
use LPWork\Middleware\Exceptions\MiddlewareNotFoundException;

/**
 * Resolves console middleware resolver values into runtime objects.
 */
final readonly class ConsoleMiddlewareResolver
{
    /**
     * Creates a new ConsoleMiddlewareResolver instance.
     */
    public function __construct(
        private Application $app,
        private ConsoleMiddlewareStack $globalMiddleware,
        private bool $productionEnvironment = false,
    ) {}

    /**
     * @return list<ConsoleMiddleware>
     */
    public function resolve(Command $command): array
    {
        $middleware = $this->globalMiddleware->all();

        if ($command instanceof HasConsoleMiddleware) {
            $middleware = [...$middleware, ...$command->middleware()];
        }

        return $this->middlewareList($command, $middleware);
    }

    /**
     * @param list<string> $middlewareClasses
     *
     * @return list<ConsoleMiddleware>
     */
    private function middlewareList(Command $command, array $middlewareClasses): array
    {
        $middleware = [];

        foreach ($middlewareClasses as $middlewareClass) {
            $middleware[] = $this->middleware($command, $middlewareClass);
        }

        return $middleware;
    }

    private function middleware(Command $command, string $middleware): ConsoleMiddleware
    {
        if ($middleware === ProductionSafetyMiddleware::class && $command instanceof ProductionSensitiveCommand) {
            return new ProductionSafetyMiddleware($command, $this->productionEnvironment);
        }

        if (!class_exists($middleware)) {
            throw new MiddlewareNotFoundException($middleware);
        }

        if (!is_a($middleware, ConsoleMiddleware::class, true)) {
            throw new InvalidMiddlewareException($middleware);
        }

        $instance = $this->app->container()->make($middleware);

        if (!$instance instanceof ConsoleMiddleware) {
            throw new InvalidMiddlewareException($middleware);
        }

        return $instance;
    }
}
