<?php

declare(strict_types=1);

namespace Tests\support\testing\Cache;

use LPWork\Cache\CacheStore;
use PHPUnit\Framework\Assert;

final readonly class TestCacheStore
{
    private function __construct(
        private CacheStore $store,
        private InMemoryCacheDriver $driver,
    ) {}

    public static function create(string $name = 'test'): self
    {
        $driver = new InMemoryCacheDriver();

        return new self(new CacheStore($name, $driver), $driver);
    }

    public function store(): CacheStore
    {
        return $this->store;
    }

    public function put(string $key, mixed $value): self
    {
        $this->store->put($key, $value);

        return $this;
    }

    public function forget(string $key): self
    {
        $this->store->forget($key);

        return $this;
    }

    public function clear(): self
    {
        $this->store->clear();

        return $this;
    }

    public function assertHas(string $key, mixed ...$value): self
    {
        Assert::assertTrue($this->driver->has($key), sprintf('Cache key [%s] does not exist.', $key));

        if ($value !== []) {
            Assert::assertSame($value[0], $this->store->get($key), sprintf('Unexpected cache value for [%s].', $key));
        }

        return $this;
    }

    public function assertMissing(string $key): self
    {
        Assert::assertFalse($this->driver->has($key), sprintf('Cache key [%s] exists unexpectedly.', $key));

        return $this;
    }
}
