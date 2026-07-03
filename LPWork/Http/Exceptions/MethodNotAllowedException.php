<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports method not allowed exception failures.
 */
final class MethodNotAllowedException extends HttpStatusException
{
    /**
     * @param non-empty-list<string> $allowedMethods
     */
    public function __construct(
        private readonly array $allowedMethods,
        string $message = '',
    ) {
        parent::__construct($message, ['Allow' => implode(', ', $allowedMethods)]);
    }

    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 405;
    }

    /**
     * @return non-empty-list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
