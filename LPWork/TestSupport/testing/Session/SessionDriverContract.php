<?php

declare(strict_types=1);

namespace Tests\support\testing\Session;

use LPWork\Session\Contracts\SessionDriver;
use PHPUnit\Framework\Assert;

final readonly class SessionDriverContract
{
    public function __construct(
        private SessionDriver $driver,
    ) {}

    public function verifiesSessionPersistence(): void
    {
        $session = $this->driver->start();
        $session->put('user_id', 15);
        $session->flash('status', 'saved');
        $this->driver->save($session);

        $restored = $this->driver->start();

        Assert::assertSame(15, $restored->get('user_id'));
        Assert::assertSame('saved', $restored->get('status'));

        $restored->forget('user_id');
        $this->driver->save($restored);

        Assert::assertFalse($this->driver->start()->has('user_id'));
    }
}
