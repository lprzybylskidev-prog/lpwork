<?php

declare(strict_types=1);

namespace LPWork\Session\Contracts;

use LPWork\Session\Session;

/**
 * Defines the contract for session driver.
 */
interface SessionDriver
{
    /**
     * Performs the start operation.
     */
    public function start(): Session;

    /**
     * Registers or stores save.
     */
    public function save(Session $session): void;
}
