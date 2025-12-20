<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Contract;

/**
 * Generates secure session identifiers.
 */
interface SessionIdGeneratorInterface
{
    /**
     * Generates a new session identifier.
     *
     * @return string
     */
    public function generate(): string;
}
