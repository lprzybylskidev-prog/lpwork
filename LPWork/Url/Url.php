<?php

declare(strict_types=1);

namespace LPWork\Url;

use DateTimeInterface;
use LPWork\Shared\Concerns\PreventsSerialization;
use LPWork\Url\Exceptions\UrlGeneratorNotConfiguredException;

/**
 * Represents the url framework component.
 */
final class Url
{
    use PreventsSerialization;

    private static ?UrlGenerator $generator = null;

    private function __construct() {}

    private function __clone() {}

    /**
     * Registers or stores set generator.
     */
    public static function setGenerator(UrlGenerator $generator): void
    {
        self::$generator = $generator;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public static function reset(): void
    {
        self::$generator = null;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->route($name, $parameters, $absolute);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function path(string $name, array $parameters = []): string
    {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->path($name, $parameters);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function to(string $path, array $query = [], bool $absolute = true): string
    {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->to($path, $query, $absolute);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function signedRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->signedRoute($name, $parameters, $absolute);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function temporarySignedRoute(
        string $name,
        DateTimeInterface|int $expires,
        array $parameters = [],
        bool $absolute = true,
    ): string {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->temporarySignedRoute($name, $expires, $parameters, $absolute);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function signedTo(string $path, array $query = [], bool $absolute = true): string
    {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->signedTo($path, $query, $absolute);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function temporarySignedTo(
        string $path,
        DateTimeInterface|int $expires,
        array $query = [],
        bool $absolute = true,
    ): string {
        if (self::$generator === null) {
            throw new UrlGeneratorNotConfiguredException();
        }

        return self::$generator->temporarySignedTo($path, $expires, $query, $absolute);
    }
}
