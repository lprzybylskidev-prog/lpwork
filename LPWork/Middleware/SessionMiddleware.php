<?php

declare(strict_types=1);

namespace LPWork\Middleware;

use Closure;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Drivers\PersistentSessionDriver;

/**
 * Applies session middleware middleware behavior.
 */
final readonly class SessionMiddleware implements Middleware
{
    /**
     * Creates a new SessionMiddleware instance.
     */
    public function __construct(
        private SessionDriver $driver,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $driver = $this->driver();
        $session = $driver instanceof PersistentSessionDriver
            ? $driver->startWithCookies($request->cookies())
            : $driver->start();
        $session->ageFlashData();

        try {
            $response = $next($request->withSession($session));
        } finally {
            $driver->save($session);
        }

        if ($driver instanceof PersistentSessionDriver) {
            $cookie = $driver->queuedCookie();

            if ($cookie !== null) {
                return $response->withCookie($cookie);
            }
        }

        return $response;
    }

    private function driver(): SessionDriver
    {
        return $this->driver;
    }
}
