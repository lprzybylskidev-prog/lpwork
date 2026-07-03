<?php

declare(strict_types=1);

namespace LPWork\Console;

use Closure;
use LPWork\Console\Contracts\ConsoleMiddleware;
use LPWork\Responses\ConsoleResponse;

/**
 * Represents the console middleware pipeline framework component.
 */
final readonly class ConsoleMiddlewarePipeline
{
    /**
     * @param list<ConsoleMiddleware> $middleware
     */
    public function __construct(
        private array $middleware = [],
    ) {}

    /**
     * @param Closure(Input): ConsoleResponse $destination
     */
    public function handle(Input $input, Closure $destination): ConsoleResponse
    {
        $next = $destination;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn(Input $input): ConsoleResponse => $middleware->handle($input, $next);
        }

        return $next($input);
    }
}
