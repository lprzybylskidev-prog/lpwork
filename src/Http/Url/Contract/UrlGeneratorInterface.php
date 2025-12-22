<?php
declare(strict_types=1);

namespace LPwork\Http\Url\Contract;

/**
 * Generates URLs for routes and arbitrary paths.
 */
interface UrlGeneratorInterface
{
    /**
     * Generates URL for a named route.
     *
     * @param string               $name
     * @param array<string, string|int|float> $parameters
     * @param array<string, string|int|float> $query
     * @param bool                 $absolute
     *
     * @return string
     */
    public function route(
        string $name,
        array $parameters = [],
        array $query = [],
        bool $absolute = true,
    ): string;

    /**
     * Builds URL for an arbitrary path.
     *
     * @param string               $path
     * @param array<string, string|int|float> $query
     * @param bool                 $absolute
     *
     * @return string
     */
    public function to(string $path, array $query = [], bool $absolute = true): string;
}
