<?php

declare(strict_types=1);

namespace LPWork\Support;

use DateTimeInterface;
use LPWork\DebugDump\Debug;
use LPWork\DebugDump\DebugDumpLabel;
use LPWork\Http\FormErrors;
use LPWork\Http\MethodSpoofing;
use LPWork\Http\OldInput;
use LPWork\Http\Redirect;
use LPWork\Http\Redirector;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfInput;
use LPWork\Session\Session;
use LPWork\Storage\StorageManager;
use LPWork\Translation\Translator;
use LPWork\Url\Url;
use LPWork\View\ViewFactory;
use Stringable;

/**
 * Represents the helpers framework component.
 */
final class Helpers
{
    /**
     * Performs the debug operation.
     */
    public static function debug(string $label): DebugDumpLabel
    {
        return Debug::label($label);
    }

    /**
     * Performs the d operation.
     */
    public static function d(mixed ...$values): mixed
    {
        return Debug::d(...$values);
    }

    /**
     * Performs the dd operation.
     */
    public static function dd(mixed ...$values): never
    {
        Debug::dd(...$values);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return Url::route($name, $parameters, $absolute);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function path(string $name, array $parameters = []): string
    {
        return Url::path($name, $parameters);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function url(string $path, array $query = [], bool $absolute = true): string
    {
        return Url::to($path, $query, $absolute);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public static function signedRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        return Url::signedRoute($name, $parameters, $absolute);
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
        return Url::temporarySignedRoute($name, $expires, $parameters, $absolute);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function signedUrl(string $path, array $query = [], bool $absolute = true): string
    {
        return Url::signedTo($path, $query, $absolute);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function temporarySignedUrl(
        string $path,
        DateTimeInterface|int $expires,
        array $query = [],
        bool $absolute = true,
    ): string {
        return Url::temporarySignedTo($path, $expires, $query, $absolute);
    }

    /**
     * Performs the storage url operation.
     */
    public static function storageUrl(StorageManager $storage, string $path, ?string $disk = null): string
    {
        return $storage->url($path, $disk);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function redirect(string $location, int $statusCode = 302, array $headers = []): HttpResponse
    {
        return Redirect::to($location, $statusCode, $headers);
    }

    /**
     * @param array<string, scalar> $parameters
     * @param array<string, string> $headers
     */
    public static function redirectRoute(
        string $name,
        array $parameters = [],
        int $statusCode = 302,
        bool $absolute = true,
        array $headers = [],
    ): HttpResponse {
        return Redirect::route($name, $parameters, $statusCode, $absolute, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function back(HttpRequest $request, string $fallback = '/', int $statusCode = 302, array $headers = []): HttpResponse
    {
        return Redirect::back($request, $fallback, $statusCode, $headers);
    }

    /**
     * Performs the redirects operation.
     */
    public static function redirects(Session $session): Redirector
    {
        return Redirect::withSession($session);
    }

    /**
     * Returns method input.
     */
    public static function methodInput(string $method): string
    {
        return MethodSpoofing::input($method);
    }

    /**
     * Performs the csrf input operation.
     */
    public static function csrfInput(Session $session, ?CsrfConfig $config = null): string
    {
        if ($config !== null) {
            return CsrfInput::fromConfig($session, $config);
        }

        return CsrfInput::input($session);
    }

    /**
     * Performs the old operation.
     */
    public static function old(Session $session, string $key, mixed $default = null): mixed
    {
        return OldInput::get($session, $key, $default);
    }

    /**
     * Performs the error operation.
     */
    public static function error(Session $session, string $key, mixed $default = null): mixed
    {
        return FormErrors::get($session, $key, $default);
    }

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    public static function trans(Translator $translator, string $key, array $parameters = [], ?string $locale = null): string
    {
        return $translator->get($key, $parameters, $locale);
    }

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    public static function transText(Translator $translator, string $text, array $parameters = [], ?string $locale = null): string
    {
        return $translator->text($text, $parameters, $locale);
    }

    /**
     * @param array<string, mixed>|object $data
     */
    public static function view(ViewFactory $views, string $name, array|object $data = []): string
    {
        return $views->render($name, $data);
    }
}
