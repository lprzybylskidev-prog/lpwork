<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Requests\HttpRequest;
use LPWork\Session\Session;

/**
 * Represents the redirector framework component.
 */
final readonly class Redirector
{
    /**
     * Creates a new Redirector instance.
     */
    public function __construct(private Session $session) {}

    /**
     * @param array<string, string> $headers
     */
    public function to(string $location, int $statusCode = 302, array $headers = []): RedirectFlow
    {
        return new RedirectFlow(Redirect::to($location, $statusCode, $headers), $this->session);
    }

    /**
     * @param array<string, scalar> $parameters
     * @param array<string, string> $headers
     */
    public function route(
        string $name,
        array $parameters = [],
        int $statusCode = 302,
        bool $absolute = true,
        array $headers = [],
    ): RedirectFlow {
        return new RedirectFlow(Redirect::route($name, $parameters, $statusCode, $absolute, $headers), $this->session);
    }

    /**
     * @param array<string, string> $headers
     */
    public function back(HttpRequest $request, string $fallback = '/', int $statusCode = 302, array $headers = []): RedirectFlow
    {
        return new RedirectFlow(Redirect::back($request, $fallback, $statusCode, $headers), $this->session);
    }
}
