<?php

declare(strict_types=1);

namespace Tests\support\events;

final class EventLog
{
    /**
     * @var list<string>
     */
    private array $entries = [];

    public function add(string $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return list<string>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
