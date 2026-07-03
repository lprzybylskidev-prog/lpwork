<?php

declare(strict_types=1);

namespace Tests\support\testing\Cli;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\console\SecondConsoleMiddleware;

final class ApplicationCliIntegrationCommand implements Command, DescribesInput, HasConsoleMiddleware
{
    public function name(): string
    {
        return 'app:cli';
    }

    public function description(): string
    {
        return 'Application CLI integration command.';
    }

    public function handle(Input $input, Output $output): int
    {
        $output->writeln('arguments=' . implode('|', $input->arguments()));
        $output->writeln('path=' . $this->optionValue($input->option('path')));
        $output->writeln('tags=' . implode('|', array_map(
            static fn(string|bool|int $value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            $input->optionValues('tag'),
        )));
        $output->writeln('force=' . ($input->hasOption('force') ? 'yes' : 'no'));

        if ($input->hasOption('fail')) {
            $output->error('application command failed');

            return 7;
        }

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::required('subject', 'Subject to render.'),
            ConsoleArgument::optional('mode', 'Optional mode to render.'),
        ];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::flag('force', 'f', 'Force the command behavior.'),
            ConsoleOption::flag('fail', description: 'Return a controlled failure exit code.'),
            ConsoleOption::value('path', 'p', 'Path value to render.'),
            ConsoleOption::multiple('tag', description: 'Tags to render.'),
        ];
    }

    /**
     * @return list<string>
     */
    public function middleware(): array
    {
        return [
            SecondConsoleMiddleware::class,
        ];
    }

    /**
     * @param string|bool|int|list<string|bool|int>|null $value
     */
    private function optionValue(string|bool|int|array|null $value): string
    {
        if ($value === null) {
            return 'missing';
        }

        if (is_array($value)) {
            return implode('|', array_map(
                static fn(string|bool|int $item): string => is_bool($item) ? ($item ? 'true' : 'false') : (string) $item,
                $value,
            ));
        }

        return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
    }
}
