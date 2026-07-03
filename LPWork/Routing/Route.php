<?php

declare(strict_types=1);

namespace LPWork\Routing;

/**
 * Represents the route framework component.
 */
final class Route
{
    private ?string $name = null;

    /**
     * @var list<string>
     */
    private array $middleware = [];

    /**
     * @var array<string, string>
     */
    private array $wheres = [];

    private ?string $regex = null;

    private bool $api = false;

    /**
     * @var list<string>|null
     */
    private ?array $parameterNames = null;

    /**
     * @param non-empty-list<string> $methods
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $path,
        private readonly RouteAction $action,
    ) {}

    /**
     * @return non-empty-list<string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Performs the action operation.
     */
    public function action(): RouteAction
    {
        return $this->action;
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Registers or stores set name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string|list<string> $middleware
     */
    public function middleware(string|array $middleware): self
    {
        foreach ((array) $middleware as $item) {
            $this->middleware[] = $item;
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function middlewareList(): array
    {
        return $this->middleware;
    }

    /**
     * Performs the api operation.
     */
    public function api(): self
    {
        $this->api = true;

        return $this;
    }

    /**
     * Reports whether is api.
     */
    public function isApi(): bool
    {
        return $this->api;
    }

    /**
     * Performs the where operation.
     */
    public function where(string $parameter, string $pattern): self
    {
        $this->wheres[$parameter] = $pattern;
        $this->regex = null;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function wheres(): array
    {
        return $this->wheres;
    }

    /**
     * Reports whether matches method.
     */
    public function matchesMethod(string $method): bool
    {
        $method = strtoupper($method);

        return in_array($method, $this->effectiveMethods(), true);
    }

    /**
     * Reports whether matches explicit method.
     */
    public function matchesExplicitMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods, true);
    }

    /**
     * @return non-empty-list<string>
     */
    public function effectiveMethods(): array
    {
        if (!in_array('GET', $this->methods, true) || in_array('HEAD', $this->methods, true)) {
            return $this->methods;
        }

        return [...$this->methods, 'HEAD'];
    }

    /**
     * Reports whether matches path.
     */
    public function matchesPath(string $path): bool
    {
        return preg_match($this->regex(), $this->normalizePath($path)) === 1;
    }

    /**
     * @return array<string, string>
     */
    public function parameters(string $path): array
    {
        preg_match($this->regex(), $this->normalizePath($path), $matches);

        $parameters = [];

        foreach ($this->parameterNames() as $parameter) {
            if (isset($matches[$parameter])) {
                $parameters[$parameter] = $matches[$parameter];
            }
        }

        return $parameters;
    }

    /**
     * @return list<string>
     */
    public function parameterNames(): array
    {
        if ($this->parameterNames !== null) {
            return $this->parameterNames;
        }

        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?:\?)?}/', $this->path, $matches);

        $this->parameterNames = $matches[1];

        return $this->parameterNames;
    }

    /**
     * Reports whether has optional parameter.
     */
    public function hasOptionalParameter(string $parameter): bool
    {
        return preg_match('/\{' . preg_quote($parameter, '/') . '\?}/', $this->path) === 1;
    }

    private function regex(): string
    {
        if ($this->regex !== null) {
            return $this->regex;
        }

        if ($this->path === '/') {
            $this->regex = '#^/$#';

            return $this->regex;
        }

        $segments = explode('/', trim($this->path, '/'));
        $pattern = '';

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)(\?)?}$/', $segment, $matches) === 1) {
                $segmentPattern = sprintf('/(?P<%s>%s)', $matches[1], $this->wheres[$matches[1]] ?? '[^/]+');

                if (($matches[2] ?? '') === '?') {
                    $pattern .= '(?:' . $segmentPattern . ')?';

                    continue;
                }

                $pattern .= $segmentPattern;

                continue;
            }

            $pattern .= '/' . preg_quote($segment, '#');
        }

        $this->regex = '#^' . $pattern . '$#';

        return $this->regex;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
