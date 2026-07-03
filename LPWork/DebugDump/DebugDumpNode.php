<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

/**
 * Represents the debug dump node framework component.
 */
final readonly class DebugDumpNode
{
    /**
     * @param array<string, string> $meta
     * @param list<DebugDumpNode> $children
     */
    public function __construct(
        private string $type,
        private string $summary,
        private array $meta = [],
        private array $children = [],
        private ?string $name = null,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Performs the type operation.
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Performs the summary operation.
     */
    public function summary(): string
    {
        return $this->summary;
    }

    /**
     * @return array<string, string>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return list<DebugDumpNode>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * Reports whether has children.
     */
    public function hasChildren(): bool
    {
        return $this->children !== [];
    }
}
