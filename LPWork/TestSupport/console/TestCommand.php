<?php

declare(strict_types=1);

namespace Tests\support\console;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

final class TestCommand implements Command
{
    public function __construct(
        private readonly string $name,
        private readonly string $description = 'Test command.',
        private readonly int $exitCode = 0,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function handle(Input $input, Output $output): int
    {
        return $this->exitCode;
    }
}
