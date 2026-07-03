<?php

declare(strict_types=1);

namespace LPWork\Emitters;

use LPWork\Console\Output;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\Emitters\Exceptions\UnsupportedResponseException;
use LPWork\Responses\ConsoleResponse;
use LPWork\Responses\Contracts\Response;

/**
 * Represents the console emitter framework component.
 */
final readonly class ConsoleEmitter implements Emitter
{
    /**
     * Creates a new ConsoleEmitter instance.
     */
    public function __construct(
        private Output $output,
    ) {}

    /**
     * Performs the terminal operation.
     */
    public static function terminal(): self
    {
        return new self(Output::terminal());
    }

    /**
     * Runs emit.
     */
    public function emit(Response $response): int
    {
        if (!$response instanceof ConsoleResponse) {
            throw UnsupportedResponseException::forConsoleEmitter($response);
        }

        return $response->send($this->output);
    }
}
