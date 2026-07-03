<?php

declare(strict_types=1);

namespace Tests\support\testing\Session;

use LPWork\Session\Drivers\InMemorySessionDriver;
use LPWork\Session\Session;
use PHPUnit\Framework\Assert;

final readonly class TestSessionStore
{
    public function __construct(
        private InMemorySessionDriver $driver = new InMemorySessionDriver(),
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function seeded(array $data): self
    {
        return new self(new InMemorySessionDriver($data));
    }

    public function driver(): InMemorySessionDriver
    {
        return $this->driver;
    }

    public function session(): Session
    {
        return Session::fromArray($this->driver->data());
    }

    public function put(string $key, mixed $value): self
    {
        $session = $this->session();
        $session->put($key, $value);
        $this->driver->save($session);

        return $this;
    }

    public function forget(string $key): self
    {
        $session = $this->session();
        $session->forget($key);
        $this->driver->save($session);

        return $this;
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string> $except
     */
    public function withOldInput(array $input, array $except = []): self
    {
        $session = $this->session();
        $session->flashInput($input, $except);
        $this->driver->save($session);

        return $this;
    }

    /**
     * @param array<string, mixed> $errors
     */
    public function withErrors(array $errors): self
    {
        $session = $this->session();
        $session->flashErrors($errors);
        $this->driver->save($session);

        return $this;
    }

    public function assertHas(string $key, mixed ...$value): self
    {
        $session = $this->session();

        Assert::assertTrue($session->has($key), sprintf('Session key [%s] does not exist.', $key));

        if ($value !== []) {
            Assert::assertSame($value[0], $session->get($key), sprintf('Unexpected session value for [%s].', $key));
        }

        return $this;
    }

    public function assertMissing(string $key): self
    {
        Assert::assertFalse($this->session()->has($key), sprintf('Session key [%s] exists unexpectedly.', $key));

        return $this;
    }

    public function assertOldInput(string $key, mixed $value): self
    {
        Assert::assertSame($value, $this->session()->old($key), sprintf('Unexpected old input value for [%s].', $key));

        return $this;
    }

    public function assertError(string $key, mixed $value): self
    {
        Assert::assertSame($value, $this->session()->error($key), sprintf('Unexpected session error for [%s].', $key));

        return $this;
    }

    public function assertStarted(int $times): self
    {
        Assert::assertSame($times, $this->driver->starts, 'Unexpected session start count.');

        return $this;
    }

    public function assertSaved(int $times): self
    {
        Assert::assertSame($times, $this->driver->saves, 'Unexpected session save count.');

        return $this;
    }

    public function assertRegenerated(int $times = 1): self
    {
        Assert::assertSame($times, $this->driver->regenerations, 'Unexpected session regeneration count.');

        return $this;
    }

    public function assertInvalidated(int $times = 1): self
    {
        Assert::assertSame($times, $this->driver->invalidations, 'Unexpected session invalidation count.');

        return $this;
    }
}
