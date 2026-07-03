<?php

declare(strict_types=1);

namespace LPWork\Session\Drivers;

use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Exceptions\InvalidSessionSameSiteException;
use LPWork\Session\Exceptions\SessionSaveException;
use LPWork\Session\Exceptions\SessionStartException;
use LPWork\Session\Session;

/**
 * Persists LPWork session state through PHP's native session extension.
 */
final class PhpSessionDriver implements SessionDriver
{
    /**
     * Creates a native session driver with cookie and PHP session settings.
     */
    public function __construct(
        private readonly string $name = 'LPWORK_SESSION',
        private readonly int $lifetime = 120,
        private readonly string $path = '/',
        private readonly string $domain = '',
        private readonly bool $secure = false,
        private readonly bool $httpOnly = true,
        private readonly string $sameSite = 'Lax',
        private readonly bool $useStrictMode = true,
    ) {
        if (!in_array($this->sameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new InvalidSessionSameSiteException($this->sameSite);
        }
    }

    /**
     * Starts the native PHP session and returns the loaded framework session state.
     */
    public function start(): Session
    {
        $this->configure();

        if (session_status() === PHP_SESSION_NONE && !session_start()) {
            throw new SessionStartException();
        }

        $data = $this->stringKeyMap($_SESSION);

        return Session::fromArray($data);
    }

    /**
     * Saves framework session state back into the native PHP session.
     */
    public function save(Session $session): void
    {
        if ($session->invalidationRequested() && session_status() === PHP_SESSION_ACTIVE && !session_destroy()) {
            throw new SessionSaveException();
        }

        if ($session->regenerationRequested() && session_status() === PHP_SESSION_ACTIVE && !$session->invalidationRequested() && !session_regenerate_id(true)) {
            throw new SessionSaveException();
        }

        $_SESSION = $session->all();

        if (session_status() === PHP_SESSION_ACTIVE && !session_write_close()) {
            throw new SessionSaveException();
        }

        $session->clearLifecycleRequests();
    }

    /**
     * Normalizes PHP session data to the framework session payload shape.
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function stringKeyMap(array $values): array
    {
        $map = [];

        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $map[$key] = $value;
        }

        return $map;
    }

    private function configure(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        session_name($this->name);
        ini_set('session.use_strict_mode', $this->useStrictMode ? '1' : '0');
        session_set_cookie_params([
            'lifetime' => $this->lifetime * 60,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite(),
        ]);
    }

    /**
     * @return 'Lax'|'Strict'|'None'
     */
    private function sameSite(): string
    {
        return match ($this->sameSite) {
            'Strict' => 'Strict',
            'None' => 'None',
            default => 'Lax',
        };
    }
}
