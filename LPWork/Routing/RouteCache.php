<?php

declare(strict_types=1);

namespace LPWork\Routing;

use function array_is_list;
use function is_array;
use function is_bool;
use function is_string;

use LPWork\Filesystem\Filesystem;
use LPWork\Routing\Exceptions\RouteCacheException;

use function ltrim;
use function rtrim;
use function str_starts_with;
use function var_export;

/**
 * Represents the route cache framework component.
 */
final readonly class RouteCache
{
    /**
     * Creates a new RouteCache instance.
     */
    public function __construct(
        private string $basePath,
        private string $path = 'storage/framework/cache/routes.php',
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Returns path.
     */
    public function path(): string
    {
        if (str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($this->path, '/');
    }

    /**
     * Reports whether exists.
     */
    public function exists(): bool
    {
        return $this->filesystem->isFile($this->path());
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $this->filesystem->delete($this->path());
    }

    /**
     * Registers or stores write.
     */
    public function write(RouteCollection $routes): void
    {
        $this->filesystem->write(
            $this->path(),
            "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($this->serializableRoutes($routes), true) . ";\n",
        );
    }

    /**
     * Builds or returns load into.
     */
    public function loadInto(RouteCollection $routes): void
    {
        $cached = include $this->path();

        if (!is_array($cached)) {
            throw RouteCacheException::invalid($this->path());
        }

        foreach ($cached as $row) {
            if (!is_array($row)) {
                throw RouteCacheException::invalid($this->path());
            }

            $route = $this->routeFromRow($row);
            $routes->add($route);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializableRoutes(RouteCollection $routes): array
    {
        $serialized = [];

        foreach ($routes->all() as $route) {
            if ($route->action()->isClosure()) {
                throw RouteCacheException::closureRoute($route->path());
            }

            $serialized[] = [
                'methods' => $route->methods(),
                'path' => $route->path(),
                'controller' => $route->action()->controller(),
                'action' => $route->action()->method(),
                'name' => $route->name(),
                'middleware' => $route->middlewareList(),
                'wheres' => $route->wheres(),
                'api' => $route->isApi(),
            ];
        }

        return $serialized;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function routeFromRow(array $row): Route
    {
        $methods = $row['methods'] ?? null;
        $path = $row['path'] ?? null;
        $controller = $row['controller'] ?? null;
        $action = $row['action'] ?? null;
        $name = $row['name'] ?? null;
        $middleware = $row['middleware'] ?? null;
        $wheres = $row['wheres'] ?? null;
        $api = $row['api'] ?? null;
        $methods = $this->nonEmptyStringList($methods);
        $middleware = $this->stringList($middleware);
        $wheres = $this->stringMap($wheres);

        if (
            $methods === null
            || !is_string($path)
            || !is_string($controller)
            || !is_string($action)
            || ($name !== null && !is_string($name))
            || $middleware === null
            || $wheres === null
            || !is_bool($api)
        ) {
            throw RouteCacheException::invalid($this->path());
        }

        $route = new Route($methods, $path, RouteAction::fromArray([$controller, $action]));
        $route->middleware($middleware);

        foreach ($wheres as $parameter => $pattern) {
            $route->where($parameter, $pattern);
        }

        if ($api) {
            $route->api();
        }

        if ($name !== null) {
            $route->setName($name);
        }

        return $route;
    }

    /**
     * @return non-empty-list<string>|null
     */
    private function nonEmptyStringList(mixed $value): ?array
    {
        $list = $this->stringList($value);

        if ($list === null || $list === []) {
            return null;
        }

        return $list;
    }

    /**
     * @return list<string>|null
     */
    private function stringList(mixed $value): ?array
    {
        if (!is_array($value) || !array_is_list($value)) {
            return null;
        }

        $list = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                return null;
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @return array<string, string>|null
     */
    private function stringMap(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $map = [];

        foreach ($value as $key => $item) {
            if (!is_string($key) || !is_string($item)) {
                return null;
            }

            $map[$key] = $item;
        }

        return $map;
    }
}
