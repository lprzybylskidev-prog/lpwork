<?php

declare(strict_types=1);

namespace Tests\support\testing\Throttle;

use LPWork\Throttle\Contracts\ThrottleStorage;
use PHPUnit\Framework\Assert;

final readonly class ThrottleStorageContract
{
    public function __construct(
        private ThrottleStorage $storage,
    ) {}

    public function verifiesHitCountersAndDecayWindows(): void
    {
        $first = $this->storage->hit('contract:user:15', 60, 1000);
        $second = $this->storage->hit('contract:user:15', 60, 1010);
        $expired = $this->storage->hit('contract:user:15', 60, 1060);

        Assert::assertSame(1, $first->attempts());
        Assert::assertSame(60, $first->retryAfter());
        Assert::assertSame(2, $second->attempts());
        Assert::assertSame(50, $second->retryAfter());
        Assert::assertSame(1, $expired->attempts());
        Assert::assertSame(60, $expired->retryAfter());
    }
}
