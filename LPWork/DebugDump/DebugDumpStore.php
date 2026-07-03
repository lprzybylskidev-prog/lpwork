<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

/**
 * Represents the debug dump store framework component.
 */
final class DebugDumpStore
{
    /**
     * @var list<DebugDumpRecord>
     */
    private array $records = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(DebugDumpRecord $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return list<DebugDumpRecord>
     */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * @return list<DebugDumpRecord>
     */
    public function flush(): array
    {
        $records = $this->records;
        $this->records = [];

        return $records;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->records = [];
    }
}
