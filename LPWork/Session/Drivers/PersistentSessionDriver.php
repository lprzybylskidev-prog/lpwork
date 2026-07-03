<?php

declare(strict_types=1);

namespace LPWork\Session\Drivers;

use LPWork\Http\Cookie;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Session;

/**
 * Represents the persistent session driver framework component.
 */
abstract class PersistentSessionDriver implements SessionDriver
{
    private ?string $id = null;

    private ?Cookie $queuedCookie = null;

    /**
     * Creates a new PersistentSessionDriver instance.
     */
    public function __construct(
        private readonly string $name,
        private readonly int $lifetime,
        private readonly string $path = '/',
        private readonly string $domain = '',
        private readonly bool $secure = false,
        private readonly bool $httpOnly = true,
        private readonly string $sameSite = 'Lax',
    ) {}

    /**
     * Performs the start operation.
     */
    public function start(): Session
    {
        return $this->startWithCookies([]);
    }

    /**
     * @param array<string, mixed> $cookies
     */
    public function startWithCookies(array $cookies): Session
    {
        $this->id = $this->sessionId($cookies);
        $payload = $this->read($this->id);

        return Session::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * Registers or stores save.
     */
    public function save(Session $session): void
    {
        $id = $this->id ?? $this->sessionId([]);

        if ($session->invalidationRequested()) {
            $this->delete($id);
            $id = $this->newId();
        } elseif ($session->regenerationRequested()) {
            $this->delete($id);
            $id = $this->newId();
        }

        $this->write($id, $session->all(), $this->lifetime * 60);
        $this->id = $id;
        $this->queuedCookie = $this->cookie($id);
        $session->clearLifecycleRequests();
    }

    /**
     * Returns queued cookie.
     */
    public function queuedCookie(): ?Cookie
    {
        return $this->queuedCookie;
    }

    /**
     * @return array<string, mixed>|null
     */
    abstract protected function read(string $id): ?array;

    /**
     * @param array<string, mixed> $data
     */
    abstract protected function write(string $id, array $data, int $ttlSeconds): void;

    abstract protected function delete(string $id): void;

    /**
     * @return array<string, mixed>|null
     */
    protected function sessionData(mixed $data): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        $session = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                return null;
            }

            $session[$key] = $value;
        }

        return $session;
    }

    /**
     * @param array<string, mixed> $cookies
     */
    private function sessionId(array $cookies): string
    {
        $id = $cookies[$this->name] ?? null;

        if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{32,128}$/', $id) === 1) {
            return $id;
        }

        return $this->id ?? $this->newId();
    }

    private function newId(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function cookie(string $id): Cookie
    {
        return new Cookie(
            name: $this->name,
            value: $id,
            maxAge: $this->lifetime * 60,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite(),
        );
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
