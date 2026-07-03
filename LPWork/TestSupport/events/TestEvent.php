<?php

declare(strict_types=1);

namespace Tests\support\events;

final readonly class TestEvent
{
    public function __construct(
        public string $name = 'test',
    ) {}
}
