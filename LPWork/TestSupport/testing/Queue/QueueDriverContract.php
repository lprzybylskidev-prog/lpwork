<?php

declare(strict_types=1);

namespace Tests\support\testing\Queue;

use Closure;
use LPWork\Queue\Contracts\QueueDriver;
use LPWork\Queue\QueuedJobPayload;
use PHPUnit\Framework\Assert;

final readonly class QueueDriverContract
{
    public function __construct(
        private QueueDriver $driver,
        private ?Closure $travel = null,
    ) {}

    public function verifiesImmediateQueueBehavior(QueuedJobPayload $payload): void
    {
        $this->driver->assertReady();

        Assert::assertSame($payload->id, $this->driver->push($payload));
        Assert::assertNull($this->driver->reserve('default', 60));
        Assert::assertSame(0, $this->driver->clear('default'));
        Assert::assertSame(0, $this->driver->pruneCompleted(0));
        Assert::assertSame(0, $this->driver->pruneFailed(0));
    }

    public function verifiesReservableQueueBehavior(): void
    {
        $this->driver->assertReady();

        $first = self::payload('contract-db-1');
        $second = self::payload('contract-db-2');

        Assert::assertSame($first->id, $this->driver->push($first));
        Assert::assertSame($second->id, $this->driver->push($second));
        Assert::assertNull($this->driver->reserve('emails', 60));

        $reserved = $this->driver->reserve('default', 60);
        Assert::assertNotNull($reserved);
        Assert::assertSame($first->id, $reserved->payload->id);
        Assert::assertSame(1, $reserved->attempts);

        $this->driver->release($reserved, 30);
        $released = $this->driver->reserve('default', 60);
        Assert::assertNotNull($released);
        Assert::assertSame($second->id, $released->payload->id);

        $this->driver->complete($released);

        if ($this->travel !== null) {
            ($this->travel)(30);
        }

        $retry = $this->driver->reserve('default', 60);
        Assert::assertNotNull($retry);
        Assert::assertSame($first->id, $retry->payload->id);
        Assert::assertSame(2, $retry->attempts);

        $this->driver->fail($retry, 'Failed by contract test.');
        Assert::assertSame(0, $this->driver->clear('default'));

        if ($this->travel !== null) {
            ($this->travel)(1);
        }

        Assert::assertSame(1, $this->driver->pruneCompleted(0));
        Assert::assertSame(1, $this->driver->pruneFailed(0));
        Assert::assertNull($this->driver->reserve('default', 60));
    }

    public static function payload(string $id, string $queue = 'default', int $availableAt = 1000): QueuedJobPayload
    {
        return new QueuedJobPayload(
            id: $id,
            queue: $queue,
            jobClass: NullQueueJob::class,
            body: serialize(new NullQueueJob()),
            maxAttempts: 3,
            availableAt: $availableAt,
            createdAt: 1000,
        );
    }
}
