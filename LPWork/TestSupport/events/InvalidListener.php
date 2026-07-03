<?php

declare(strict_types=1);

namespace Tests\support\events;

final class InvalidListener
{
    public function run(TestEvent $event): void {}
}
