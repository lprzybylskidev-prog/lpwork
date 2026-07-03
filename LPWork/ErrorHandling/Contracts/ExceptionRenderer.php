<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Contracts;

use Throwable;

/**
 * Defines the contract for exception renderer.
 */
interface ExceptionRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): string;
}
