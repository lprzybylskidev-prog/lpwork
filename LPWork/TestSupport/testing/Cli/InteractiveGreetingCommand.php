<?php

declare(strict_types=1);

namespace Tests\support\testing\Cli;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Console\Questioner;

final readonly class InteractiveGreetingCommand implements Command
{
    public function __construct(
        private Questioner $questioner,
    ) {}

    public function name(): string
    {
        return 'interactive:greet';
    }

    public function description(): string
    {
        return 'Greets a person from test input.';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $this->questioner->ask('Name', 'LPWork');

        $output->writeln("Hello {$name}");

        return 0;
    }
}
