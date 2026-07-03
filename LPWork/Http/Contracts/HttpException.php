<?php

declare(strict_types=1);

namespace LPWork\Http\Contracts;

use Throwable;

/**
 * Defines the contract for http exception.
 */
interface HttpException extends Throwable
{
    /**
     * Returns status code.
     */
    public function statusCode(): int;

    /**
     * @return array<string, string>
     */
    public function headers(): array;
}
