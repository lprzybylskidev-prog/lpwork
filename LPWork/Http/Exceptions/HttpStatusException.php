<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

use LPWork\Http\Contracts\HttpException;
use RuntimeException;
use Throwable;

/**
 * Reports http status exception failures.
 */
abstract class HttpStatusException extends RuntimeException implements HttpException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        string $message = '',
        private readonly array $headers = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message === '' ? (string) $this->statusCode() : $message, previous: $previous);
    }

    /**
     * @return array<string, string>
     */
    final public function headers(): array
    {
        return $this->headers;
    }
}
