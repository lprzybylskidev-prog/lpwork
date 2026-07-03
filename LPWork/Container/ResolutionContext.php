<?php

declare(strict_types=1);

namespace LPWork\Container;

/**
 * Represents the resolution context framework component.
 */
final readonly class ResolutionContext
{
    /**
     * @param list<string> $chain
     */
    private function __construct(
        private array $chain = [],
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    /**
     * Performs the contains operation.
     */
    public function contains(string $id): bool
    {
        return in_array($id, $this->chain, true);
    }

    /**
     * Registers or stores push.
     */
    public function push(string $id): self
    {
        return new self([...$this->chain, $id]);
    }

    /**
     * @return non-empty-list<string>
     */
    public function chainWith(string $id): array
    {
        return [...$this->chain, $id];
    }
}
