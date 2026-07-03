<?php

declare(strict_types=1);

namespace Tests\support\console;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

final readonly class OutputCommand implements Command
{
    public function __construct(
        private string $name,
        private string $message,
        private int $exitCode = 0,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return 'Test output command.';
    }

    public function handle(Input $input, Output $output): int
    {
        if ($this->message !== '') {
            $output->writeln($this->message);
        }

        return $this->exitCode;
    }
}
