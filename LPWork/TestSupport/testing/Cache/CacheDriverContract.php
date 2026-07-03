<?php

declare(strict_types=1);

namespace Tests\support\testing\Cache;

use Closure;
use LPWork\Cache\Contracts\CacheDriver;
use PHPUnit\Framework\Assert;

final readonly class CacheDriverContract
{
    /**
     * @param Closure(int): void|null $travel
     */
    public function __construct(
        private CacheDriver $driver,
        private ?Closure $travel = null,
    ) {}

    public function verifiesCoreCacheBehavior(): void
    {
        Assert::assertSame('fallback', $this->driver->get('contract.missing', 'fallback'));

        $this->driver->put('contract.profile', ['name' => 'Ada']);
        Assert::assertSame(['name' => 'Ada'], $this->driver->get('contract.profile'));

        Assert::assertTrue($this->driver->add('contract.lock', 'first-owner', 60));
        Assert::assertFalse($this->driver->add('contract.lock', 'second-owner', 60));
        Assert::assertSame('first-owner', $this->driver->get('contract.lock'));

        Assert::assertFalse($this->driver->forgetIfValue('contract.lock', 'second-owner'));
        Assert::assertSame('first-owner', $this->driver->get('contract.lock'));
        Assert::assertTrue($this->driver->forgetIfValue('contract.lock', 'first-owner'));
        Assert::assertSame('missing', $this->driver->get('contract.lock', 'missing'));

        $this->driver->put('contract.session', 'cached');
        $this->driver->forget('contract.session');
        Assert::assertSame('missing', $this->driver->get('contract.session', 'missing'));

        $this->driver->put('contract.first', 'cached');
        $this->driver->put('contract.second', 'also cached');
        $this->driver->clear();
        Assert::assertSame('missing', $this->driver->get('contract.first', 'missing'));
        Assert::assertSame('missing', $this->driver->get('contract.second', 'missing'));
    }

    public function verifiesExpiringCacheValues(): void
    {
        Assert::assertNotNull($this->travel, 'This cache driver contract requires a controllable clock.');

        $this->driver->put('contract.short-lived', 'cached', 10);
        ($this->travel)(11);

        Assert::assertSame('missing', $this->driver->get('contract.short-lived', 'missing'));

        Assert::assertTrue($this->driver->add('contract.expiring-lock', 'first-owner', 10));
        ($this->travel)(11);

        Assert::assertTrue($this->driver->add('contract.expiring-lock', 'second-owner', 10));
        Assert::assertSame('second-owner', $this->driver->get('contract.expiring-lock'));
    }
}
