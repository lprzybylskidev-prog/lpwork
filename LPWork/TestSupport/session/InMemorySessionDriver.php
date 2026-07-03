<?php

declare(strict_types=1);

namespace Tests\support\session;

use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Session;

final class InMemorySessionDriver implements SessionDriver
{
    public int $starts = 0;

    public int $saves = 0;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data = [],
    ) {}

    public function start(): Session
    {
        $this->starts++;

        return Session::fromArray($this->data);
    }

    public function save(Session $session): void
    {
        $this->saves++;
        $this->data = $session->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
