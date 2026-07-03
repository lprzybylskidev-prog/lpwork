<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli\Contracts;

use LPWork\Kernels\Contracts\Kernel;

/**
 * Defines the contract for cli kernel.
 */
interface CliKernel extends Kernel
{
    /**
     * Registers or stores bootstrap.
     */
    public function bootstrap(): void;

    /**
     * @param array<int, string> $argv
     */
    public function handle(array $argv): int;
}
