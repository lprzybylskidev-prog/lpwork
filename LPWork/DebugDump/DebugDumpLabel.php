<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

/**
 * Represents the debug dump label framework component.
 */
final readonly class DebugDumpLabel
{
    /**
     * Creates a new DebugDumpLabel instance.
     */
    public function __construct(
        private DebugDumper $dumper,
        private string $label,
    ) {}

    /**
     * Performs the d operation.
     */
    public function d(mixed ...$values): mixed
    {
        return $this->dumper->dumpLabeled($this->label, ...$values);
    }

    /**
     * Performs the dd operation.
     */
    public function dd(mixed ...$values): never
    {
        $this->dumper->terminateLabeled($this->label, ...$values);
    }
}
