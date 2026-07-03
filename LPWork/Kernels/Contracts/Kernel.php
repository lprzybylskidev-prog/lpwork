<?php

declare(strict_types=1);

namespace LPWork\Kernels\Contracts;

use LPWork\Foundation\Application;

/**
 * Defines the contract for kernel.
 */
interface Kernel
{
    /**
     * Creates a new Kernel instance.
     */
    public function __construct(Application $app);

    /**
     * Registers or stores bootstrap.
     */
    public function bootstrap(): void;
}
