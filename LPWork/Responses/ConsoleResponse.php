<?php

declare(strict_types=1);

namespace LPWork\Responses;

use Closure;
use LPWork\Console\Output;
use LPWork\Responses\Contracts\Response;

/**
 * Represents the console response framework component.
 */
final readonly class ConsoleResponse implements Response
{
    /**
     * @param Closure(Output): int $handler
     */
    public function __construct(
        private Closure $handler,
    ) {}

    /**
     * Performs the output operation.
     */
    public static function output(string $stdout = '', string $stderr = '', int $exitCode = 0): self
    {
        return new self(static function (Output $output) use ($stdout, $stderr, $exitCode): int {
            if ($stdout !== '') {
                $output->write($stdout);
            }

            if ($stderr !== '') {
                $output->error(rtrim($stderr, "\n"));
            }

            return $exitCode;
        });
    }

    /**
     * @param Closure(Output): int $handler
     */
    public static function using(Closure $handler): self
    {
        return new self($handler);
    }

    /**
     * Runs send.
     */
    public function send(Output $output): int
    {
        return ($this->handler)($output);
    }
}
