<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http\Contracts;

use LPWork\Kernels\Contracts\Kernel;
use LPWork\Requests\HttpRequest;

/**
 * Defines the contract for http kernel.
 */
interface HttpKernel extends Kernel
{
    /**
     * Registers or stores bootstrap.
     */
    public function bootstrap(): void;

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(?HttpRequest $request = null): int;
}
