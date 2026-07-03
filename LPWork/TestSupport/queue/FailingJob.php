<?php

declare(strict_types=1);

namespace Tests\support\queue;

use RuntimeException;

final readonly class FailingJob
{
    public function __construct(
        private string $message = 'Job failed.',
    ) {}

    public function handle(): void
    {
        throw new RuntimeException($this->message);
    }
}
