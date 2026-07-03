<?php

declare(strict_types=1);

namespace Tests\support\schedule;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

final readonly class RecordingCommand implements Command
{
    public function __construct(
        private string $path,
    ) {}

    public function name(): string
    {
        return 'test:record';
    }

    public function description(): string
    {
        return 'Record a scheduled command execution.';
    }

    public function handle(Input $input, Output $output): int
    {
        file_put_contents($this->path, implode(',', $input->arguments()) . "\n", FILE_APPEND);
        $output->writeln('recorded');

        return 0;
    }
}
