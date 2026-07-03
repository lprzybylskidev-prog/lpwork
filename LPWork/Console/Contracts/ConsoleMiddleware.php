<?php

declare(strict_types=1);

namespace LPWork\Console\Contracts;

use Closure;
use LPWork\Console\Input;
use LPWork\Responses\ConsoleResponse;

/**
 * Defines the contract for console middleware.
 */
interface ConsoleMiddleware
{
    /**
     * @param Closure(Input): ConsoleResponse $next
     */
    public function handle(Input $input, Closure $next): ConsoleResponse;
}
