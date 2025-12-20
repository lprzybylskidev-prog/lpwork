<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

/**
 * Represents a single HTTP route definition.
 */
class Route
{
    /**
     * @var array<int, string>
     */
    private array $methods;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var callable
     */
    private $handler;

    /**
     * @var string|null
     */
    private ?string $name;

    /**
     * @var array<int, string>
     */
    private array $middleware;

    /**
     * @param array<int, string> $methods
     * @param string             $path
     * @param callable           $handler
     * @param string|null        $name
     * @param array<int, string> $middleware
     */
    public function __construct(
        array $methods,
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ) {
        $this->methods = $methods;
        $this->path = $path;
        $this->handler = $handler;
        $this->name = $name;
        $this->middleware = $middleware;
    }

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return callable
     */
    public function handler(): callable
    {
        return $this->handler;
    }

    /**
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<int, string>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }
}
