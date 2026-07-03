<?php

declare(strict_types=1);

namespace Tests\support\console;

use Closure;
use LPWork\Console\Contracts\ConsoleMiddleware;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Responses\ConsoleResponse;

final class SecondConsoleMiddleware implements ConsoleMiddleware
{
    /**
     * @param Closure(Input): ConsoleResponse $next
     */
    public function handle(Input $input, Closure $next): ConsoleResponse
    {
        $response = $next($input);

        return ConsoleResponse::using(static function (Output $output) use ($response): int {
            $output->writeln('second-before');
            $exitCode = $response->send($output);
            $output->writeln('second-after');

            return $exitCode;
        });
    }
}
