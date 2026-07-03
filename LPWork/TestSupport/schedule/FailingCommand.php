<?php

declare(strict_types=1);

namespace Tests\support\schedule;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;

final class FailingCommand implements Command
{
    public function name(): string
    {
        return 'test:fail';
    }

    public function description(): string
    {
        return 'Fail a scheduled command execution.';
    }

    public function handle(Input $input, Output $output): int
    {
        return 7;
    }
}
