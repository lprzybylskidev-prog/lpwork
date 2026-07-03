<?php

declare(strict_types=1);

namespace LPWork\Url;

use DateTimeInterface;
use LPWork\Routing\RouteCollection;
use LPWork\Security\SignedUrl;
use LPWork\Url\Exceptions\MissingRouteParameterException;
use LPWork\Url\Exceptions\SignedUrlNotConfiguredException;

/**
 * Represents the url generator framework component.
 */
final readonly class UrlGenerator
{
    /**
     * Creates a new UrlGenerator instance.
     */
    public function __construct(
        private RouteCollection $routes,
        private string $baseUrl = '',
        private ?SignedUrl $signedUrl = null,
    ) {}

    /**
     * @param array<string, scalar> $parameters
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $path = $this->path($name, $parameters);

        return $absolute ? $this->absolute($path) : $path;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function path(string $name, array $parameters = []): string
    {
        $route = $this->routes->named($name);
        $path = $route->path();

        foreach ($route->parameterNames() as $parameter) {
            if (!array_key_exists($parameter, $parameters)) {
                if ($route->hasOptionalParameter($parameter)) {
                    $path = str_replace('/{' . $parameter . '?}', '', $path);
                    $path = str_replace('{' . $parameter . '?}', '', $path);

                    continue;
                }

                throw new MissingRouteParameterException($name, $parameter);
            }

            $value = rawurlencode((string) $parameters[$parameter]);
            $path = str_replace('{' . $parameter . '}', $value, $path);
            $path = str_replace('{' . $parameter . '?}', $value, $path);
            unset($parameters[$parameter]);
        }

        if ($parameters === []) {
            return $path;
        }

        return $path . '?' . http_build_query($parameters);
    }

    /**
     * @param array<string, scalar> $query
     */
    public function to(string $path, array $query = [], bool $absolute = true): string
    {
        $path = '/' . ltrim($path, '/');

        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        return $absolute ? $this->absolute($path) : $path;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function signedRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        return $this->signedUrl()->sign($this->route($name, $parameters, $absolute));
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function temporarySignedRoute(
        string $name,
        DateTimeInterface|int $expires,
        array $parameters = [],
        bool $absolute = true,
    ): string {
        return $this->signedUrl()->temporary($this->route($name, $parameters, $absolute), $expires);
    }

    /**
     * @param array<string, scalar> $query
     */
    public function signedTo(string $path, array $query = [], bool $absolute = true): string
    {
        return $this->signedUrl()->sign($this->to($path, $query, $absolute));
    }

    /**
     * @param array<string, scalar> $query
     */
    public function temporarySignedTo(
        string $path,
        DateTimeInterface|int $expires,
        array $query = [],
        bool $absolute = true,
    ): string {
        return $this->signedUrl()->temporary($this->to($path, $query, $absolute), $expires);
    }

    private function absolute(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function signedUrl(): SignedUrl
    {
        return $this->signedUrl ?? throw new SignedUrlNotConfiguredException();
    }
}
