<?php

declare(strict_types=1);

namespace Tests\support\schedule;

final readonly class RecordingJob
{
    public function __construct(
        private string $path,
        private string $message,
    ) {}

    public function handle(): void
    {
        file_put_contents($this->path, $this->message . "\n", FILE_APPEND);
    }
}
