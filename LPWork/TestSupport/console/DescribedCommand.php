<?php

declare(strict_types=1);

namespace Tests\support\console;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;

final class DescribedCommand implements Command, DescribesInput
{
    public function name(): string
    {
        return 'users:import';
    }

    public function description(): string
    {
        return 'Import users.';
    }

    public function handle(Input $input, Output $output): int
    {
        $output->writeln('imported');

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::required('file', 'CSV file path.'),
            ConsoleArgument::optional('mode', 'Import mode.'),
        ];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::flag('force', 'f', 'Force import.'),
            ConsoleOption::value('path', 'p', 'Base path.'),
            ConsoleOption::multiple('tag', description: 'Tags to apply.'),
        ];
    }
}
