<?php

declare(strict_types=1);

namespace Tests\support\foundation;

use LPWork\Foundation\Contracts\CompiledCache;

final class FakeCompiledCache implements CompiledCache
{
    public int $rebuilds = 0;

    /**
     * @param list<string> $aliases
     */
    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly array $aliases = [],
        private readonly bool $exists = false,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function aliases(): array
    {
        return $this->aliases;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function rebuild(): void
    {
        $this->rebuilds++;
    }
}
