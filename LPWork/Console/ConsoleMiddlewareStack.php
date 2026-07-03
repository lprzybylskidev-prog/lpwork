<?php

declare(strict_types=1);

namespace LPWork\Console;

/**
 * Represents the console middleware stack framework component.
 */
final class ConsoleMiddlewareStack
{
    /**
     * @var list<string>
     */
    private array $middleware = [];

    /**
     * @param string|list<string> $middleware
     */
    public function add(string|array $middleware): void
    {
        foreach ((array) $middleware as $item) {
            $this->middleware[] = $item;
        }
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->middleware;
    }
}
