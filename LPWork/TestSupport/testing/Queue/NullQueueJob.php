<?php

declare(strict_types=1);

namespace Tests\support\testing\Queue;

final readonly class NullQueueJob
{
    public function handle(): void {}
}
