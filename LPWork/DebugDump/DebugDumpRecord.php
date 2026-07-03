<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

/**
 * Represents the debug dump record framework component.
 */
final readonly class DebugDumpRecord
{
    /**
     * Creates a new DebugDumpRecord instance.
     */
    public function __construct(
        private string $id,
        private DebugDumpNode $root,
        private ?string $label = null,
    ) {}

    /**
     * Performs the id operation.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Performs the root operation.
     */
    public function root(): DebugDumpNode
    {
        return $this->root;
    }

    /**
     * Returns label.
     */
    public function label(): ?string
    {
        return $this->label;
    }
}
