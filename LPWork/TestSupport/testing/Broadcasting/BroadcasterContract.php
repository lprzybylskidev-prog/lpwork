<?php

declare(strict_types=1);

namespace Tests\support\testing\Broadcasting;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\Contracts\Broadcaster;
use PHPUnit\Framework\Assert;

final readonly class BroadcasterContract
{
    public function __construct(
        private Broadcaster $broadcaster,
        private string $name,
    ) {}

    public function verifiesBroadcastResultBehavior(): void
    {
        $message = new BroadcastMessage(['public', 'orders.1'], 'order.created', ['id' => 1]);
        $result = $this->broadcaster->broadcast($message);

        Assert::assertSame($this->name, $result->driver);
        Assert::assertSame('order.created', $result->event);
        Assert::assertSame(['public', 'orders.1'], $result->channels);
    }
}
