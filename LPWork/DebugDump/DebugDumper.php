<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

use LPWork\DebugDump\Exceptions\DebugDumpDisabledException;
use LPWork\DebugDump\Exceptions\DumpAndDieException;

/**
 * Represents the debug dumper framework component.
 */
final readonly class DebugDumper
{
    /**
     * Creates a new DebugDumper instance.
     */
    public function __construct(
        private DebugDumpInspector $inspector,
        private DebugDumpStore $store,
        private bool $enabled,
    ) {}

    /**
     * Performs the dump operation.
     */
    public function dump(mixed ...$values): mixed
    {
        if ($this->enabled) {
            $this->store->add($this->record(array_values($values), null));
        }

        return $values[0] ?? null;
    }

    /**
     * Performs the dump labeled operation.
     */
    public function dumpLabeled(string $label, mixed ...$values): mixed
    {
        if ($this->enabled) {
            $this->store->add($this->record(array_values($values), $label));
        }

        return $values[0] ?? null;
    }

    /**
     * Performs the terminate operation.
     */
    public function terminate(mixed ...$values): never
    {
        if (!$this->enabled) {
            throw new DebugDumpDisabledException();
        }

        throw new DumpAndDieException($this->record(array_values($values), null));
    }

    /**
     * Performs the terminate labeled operation.
     */
    public function terminateLabeled(string $label, mixed ...$values): never
    {
        if (!$this->enabled) {
            throw new DebugDumpDisabledException();
        }

        throw new DumpAndDieException($this->record(array_values($values), $label));
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->store->reset();
    }

    /**
     * @param list<mixed> $values
     */
    private function record(array $values, ?string $label): DebugDumpRecord
    {
        if ($values === []) {
            $values = [null];
        }

        return new DebugDumpRecord(
            id: bin2hex(random_bytes(8)),
            root: $this->inspector->inspectMany($values),
            label: $label,
        );
    }
}
