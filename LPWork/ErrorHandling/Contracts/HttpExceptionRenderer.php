<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Contracts;

use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Defines the contract for http exception renderer.
 */
interface HttpExceptionRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): HttpResponse;
}
