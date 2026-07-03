<?php

declare(strict_types=1);

namespace Tests\support\testing\Locks;

use LPWork\Locks\Contracts\LockStore;
use PHPUnit\Framework\Assert;

final readonly class LockStoreContract
{
    public function __construct(
        private LockStore $store,
    ) {}

    public function verifiesAtomicLockOwnership(): void
    {
        $first = $this->store->lock('contract.lock', 60);
        $second = $this->store->lock('contract.lock', 60);

        Assert::assertSame('contract.lock', $first->name());
        Assert::assertNotSame('', $first->owner());
        Assert::assertTrue($first->acquire());
        Assert::assertFalse($second->acquire());
        Assert::assertFalse($second->release());
        Assert::assertTrue($first->release());
        Assert::assertTrue($second->acquire());
        Assert::assertTrue($second->release());
    }
}
