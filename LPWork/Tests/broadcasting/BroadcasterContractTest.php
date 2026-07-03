<?php

declare(strict_types=1);

use LPWork\Broadcasting\Drivers\InMemoryBroadcaster;
use LPWork\Broadcasting\Drivers\LogBroadcaster;
use LPWork\Broadcasting\Drivers\NullBroadcaster;
use LPWork\Logging\LogChannel;
use Tests\support\logging\InMemoryLogDriver;
use Tests\support\testing\Broadcasting\BroadcasterContract;

it('keeps the in-memory broadcaster compatible with the shared broadcaster contract', function (): void {
    new BroadcasterContract(new InMemoryBroadcaster('sync'), 'sync')->verifiesBroadcastResultBehavior();
});

it('keeps the null broadcaster compatible with the shared broadcaster contract', function (): void {
    new BroadcasterContract(new NullBroadcaster('null'), 'null')->verifiesBroadcastResultBehavior();
});

it('keeps the log broadcaster compatible with the shared broadcaster contract', function (): void {
    $broadcaster = new LogBroadcaster('log', new LogChannel('broadcasting', new InMemoryLogDriver()));

    new BroadcasterContract($broadcaster, 'log')->verifiesBroadcastResultBehavior();
});
