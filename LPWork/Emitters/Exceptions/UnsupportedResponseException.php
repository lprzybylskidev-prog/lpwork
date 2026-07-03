<?php

declare(strict_types=1);

namespace LPWork\Emitters\Exceptions;

use InvalidArgumentException;
use LPWork\Responses\Contracts\Response;

/**
 * Reports unsupported response exception failures.
 */
final class UnsupportedResponseException extends InvalidArgumentException
{
    /**
     * Performs the for console emitter operation.
     */
    public static function forConsoleEmitter(Response $response): self
    {
        return new self(sprintf('Console emitter cannot emit %s.', $response::class));
    }

    /**
     * Performs the for http emitter operation.
     */
    public static function forHttpEmitter(Response $response): self
    {
        return new self(sprintf('Http emitter cannot emit %s.', $response::class));
    }
}
