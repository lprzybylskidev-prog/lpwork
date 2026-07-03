<?php

declare(strict_types=1);

namespace Tests\support\events;

use RuntimeException;

final class ThrowingListener
{
    public function handle(TestEvent $event): never
    {
        throw new RuntimeException('Listener failed.');
    }
}
