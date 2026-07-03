<?php

declare(strict_types=1);

namespace LPWork\Requests;

use LPWork\Console\Input;
use LPWork\Requests\Contracts\Request;

/**
 * Represents the console request framework component.
 */
final readonly class ConsoleRequest implements Request
{
    /**
     * Creates a new ConsoleRequest instance.
     */
    public function __construct(
        private Input $input,
    ) {}

    /**
     * @param array<int, string> $argv
     */
    public static function fromArgv(array $argv): self
    {
        return new self(new Input($argv));
    }

    /**
     * Returns parsed input data from this boundary.
     */
    public function input(): Input
    {
        return $this->input;
    }
}
