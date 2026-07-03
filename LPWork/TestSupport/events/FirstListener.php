<?php

declare(strict_types=1);

namespace Tests\support\events;

final readonly class FirstListener
{
    public function __construct(
        private EventLog $log,
    ) {}

    public function handle(TestEvent $event): void
    {
        $this->log->add('first:' . $event->name);
    }
}
