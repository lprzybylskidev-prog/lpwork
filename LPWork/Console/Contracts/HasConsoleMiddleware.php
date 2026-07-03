<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

/**
 * Defines the contract for has console middleware.
 */
interface HasConsoleMiddleware
{
    /**
     * @return list<string>
     */
    public function middleware(): array;
}
