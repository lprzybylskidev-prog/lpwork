<?php

declare(strict_types=1);

namespace LPWork\Session\Drivers;

use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Session;

/**
 * Represents the in memory session driver framework component.
 */
final class InMemorySessionDriver implements SessionDriver
{
    public int $starts = 0;

    public int $saves = 0;

    public int $regenerations = 0;

    public int $invalidations = 0;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data = []) {}

    /**
     * Performs the start operation.
     */
    public function start(): Session
    {
        $this->starts++;

        return Session::fromArray($this->data);
    }

    /**
     * Registers or stores save.
     */
    public function save(Session $session): void
    {
        $this->saves++;

        if ($session->regenerationRequested()) {
            $this->regenerations++;
        }

        if ($session->invalidationRequested()) {
            $this->invalidations++;
        }

        $this->data = $session->all();
        $session->clearLifecycleRequests();
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
