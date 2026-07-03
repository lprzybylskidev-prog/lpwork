<?php

declare(strict_types=1);

namespace LPWork\Emitters\Contracts;

use LPWork\Responses\Contracts\Response;

/**
 * Defines the contract for emitter.
 */
interface Emitter
{
    /**
     * Runs emit.
     */
    public function emit(Response $response): int;
}
