<?php
declare(strict_types=1);

namespace LPwork\Http\Request;

/**
 * Typed container for per-request routing context.
 */
final class RequestContext
{
    public const ATTRIBUTE = 'lpwork.request_context';

    /**
     * @var string|null
     */
    private ?string $routeName;

    /**
     * @var mixed
     */
    private mixed $handler;

    /**
     * @var array<int, callable|string>
     */
    private array $middleware;

    /**
     * @var array<string, string>
     */
    private array $parameters;

    /**
     * @param string|null                 $routeName
     * @param mixed                       $handler
     * @param array<int, callable|string> $middleware
     * @param array<string, string>       $parameters
     */
    public function __construct(
        ?string $routeName,
        mixed $handler,
        array $middleware,
        array $parameters,
    ) {
        $this->routeName = $routeName;
        $this->handler = $handler;
        $this->middleware = $middleware;
        $this->parameters = $parameters;
    }

    /**
     * @return string|null
     */
    public function routeName(): ?string
    {
        return $this->routeName;
    }

    /**
     * @return mixed
     */
    public function handler(): mixed
    {
        return $this->handler;
    }

    /**
     * @return array<int, callable|string>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }
}
