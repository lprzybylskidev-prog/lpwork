<?php

declare(strict_types=1);

namespace Tests\support\console;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Input;
use LPWork\Console\Output;

final class MiddlewareCommand implements Command, HasConsoleMiddleware
{
    public function name(): string
    {
        return 'middleware';
    }

    public function description(): string
    {
        return 'Middleware command.';
    }

    public function handle(Input $input, Output $output): int
    {
        $output->writeln('command');

        return 0;
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
}
