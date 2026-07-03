<?php

declare(strict_types=1);

namespace Tests\support\events;

final readonly class SecondListener
{
    public function __construct(
        private EventLog $log,
    ) {}

    public function handle(TestEvent $event): void
    {
        $this->log->add('second:' . $event->name);
    }
}
