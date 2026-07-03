<?php

declare(strict_types=1);

namespace Tests\support\console;

use Closure;
use LPWork\Console\Contracts\ConsoleMiddleware;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Responses\ConsoleResponse;

final class FirstConsoleMiddleware implements ConsoleMiddleware
{
    /**
     * @param Closure(Input): ConsoleResponse $next
     */
    public function handle(Input $input, Closure $next): ConsoleResponse
    {
        $response = $next($input);

        return ConsoleResponse::using(static function (Output $output) use ($response): int {
            $output->writeln('first-before');
            $exitCode = $response->send($output);
            $output->writeln('first-after');

            return $exitCode;
        });
    }
}
