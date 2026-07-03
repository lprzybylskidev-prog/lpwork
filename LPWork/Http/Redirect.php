<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Session\Session;
use LPWork\Url\Url;

/**
 * Represents the redirect framework component.
 */
final readonly class Redirect
{
    /**
     * Returns a copy with with session applied.
     */
    public static function withSession(Session $session): Redirector
    {
        return new Redirector($session);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function to(string $location, int $statusCode = 302, array $headers = []): HttpResponse
    {
        return HttpResponse::redirect($location, $statusCode, $headers);
    }

    /**
     * @param array<string, scalar> $parameters
     * @param array<string, string> $headers
     */
    public static function route(
        string $name,
        array $parameters = [],
        int $statusCode = 302,
        bool $absolute = true,
        array $headers = [],
    ): HttpResponse {
        return self::to(Url::route($name, $parameters, $absolute), $statusCode, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function back(HttpRequest $request, string $fallback = '/', int $statusCode = 302, array $headers = []): HttpResponse
    {
        return self::to($request->header('Referer', $fallback) ?? $fallback, $statusCode, $headers);
    }

    /**
     * Returns a copy with with applied.
     */
    public static function with(HttpResponse $response, Session $session, string $key, mixed $value): HttpResponse
    {
        $session->flash($key, $value);

        return $response;
    }

    /**
     * @param array<string, mixed> $input
     */
    /**
     * @param array<string, mixed> $input
     * @param list<string> $except
     */
    public static function withInput(HttpResponse $response, Session $session, array $input, array $except = []): HttpResponse
    {
        OldInput::flash($session, $input, $except);

        return $response;
    }

    /**
     * @param array<string, mixed> $errors
     */
    public static function withErrors(HttpResponse $response, Session $session, array $errors): HttpResponse
    {
        FormErrors::flash($session, $errors);

        return $response;
    }
}
