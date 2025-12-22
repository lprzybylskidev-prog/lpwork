<?php
declare(strict_types=1);

namespace LPwork\Http\Url;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Http\Routing\Route;
use LPwork\Http\Routing\RouteCollection;
use LPwork\Http\Routing\RouteLoader;
use LPwork\Http\Url\Contract\UrlGeneratorInterface;

/**
 * Generates URLs for named routes and arbitrary paths.
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var string
     */
    private string $baseUrl;

    /**
     * @var array<string, Route>
     */
    private array $routesByName;

    /**
     * @param ConfigRepositoryInterface $config
     * @param RouteLoader               $routeLoader
     */
    public function __construct(ConfigRepositoryInterface $config, RouteLoader $routeLoader)
    {
        $this->baseUrl = \rtrim($config->getString('app.url', ''), '/');
        $this->routesByName = $this->indexRoutes($routeLoader->load());
    }

    /**
     * @inheritDoc
     */
    public function route(
        string $name,
        array $parameters = [],
        array $query = [],
        bool $absolute = true,
    ): string {
        if (!isset($this->routesByName[$name])) {
            return $this->to('/', [], $absolute);
        }

        $path = $this->replaceParameters($this->routesByName[$name]->path(), $parameters);

        return $this->to($path, $query, $absolute);
    }

    /**
     * @inheritDoc
     */
    public function to(string $path, array $query = [], bool $absolute = true): string
    {
        $normalizedPath = '/' . \ltrim($path, '/');

        if ($absolute && $this->baseUrl !== '') {
            $normalizedPath = $this->baseUrl . $normalizedPath;
        }

        if ($query !== []) {
            $normalizedPath .= '?' . \http_build_query($query);
        }

        return $normalizedPath;
    }

    /**
     * @param RouteCollection $routes
     *
     * @return array<string, Route>
     */
    private function indexRoutes(RouteCollection $routes): array
    {
        $indexed = [];

        foreach ($routes->all() as $route) {
            $name = $route->name();

            if ($name === null) {
                continue;
            }

            $indexed[$name] = $route;
        }

        return $indexed;
    }

    /**
     * @param string                              $path
     * @param array<string, string|int|float> $parameters
     *
     * @return string
     */
    private function replaceParameters(string $path, array $parameters): string
    {
        $replaced = $path;

        foreach ($parameters as $key => $value) {
            $replaced = \str_replace('{' . $key . '}', (string) $value, $replaced);
        }

        return $replaced;
    }
}
