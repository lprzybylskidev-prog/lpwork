<?php

declare(strict_types=1);

namespace LPWork\Shared\Concerns;

use LPWork\Shared\Exceptions\SingletonInstanceException;

/**
 * Provides shared prevents serialization behavior.
 */
trait PreventsSerialization
{
    /**
     * Performs the wakeup operation.
     */
    public function __wakeup(): void
    {
        throw new SingletonInstanceException(sprintf('Cannot unserialize %s.', static::class));
    }

    /**
     * @return array<never, never>
     */
    public function __serialize(): array
    {
        throw new SingletonInstanceException(sprintf('Cannot serialize %s.', static::class));
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        throw new SingletonInstanceException(sprintf('Cannot unserialize %s.', static::class));
    }
}
