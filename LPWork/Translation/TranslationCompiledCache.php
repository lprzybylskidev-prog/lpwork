<?php

declare(strict_types=1);

namespace LPWork\Translation;

use LPWork\Foundation\Contracts\ReadableCompiledCache;

/**
 * Represents the translation compiled cache framework component.
 */
final readonly class TranslationCompiledCache implements ReadableCompiledCache
{
    /**
     * Creates a new TranslationCompiledCache instance.
     */
    public function __construct(private TranslationCache $cache) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'translations';
    }

    /**
     * Returns label.
     */
    public function label(): string
    {
        return 'Translation cache';
    }

    /**
     * Registers or stores aliases.
     */
    public function aliases(): array
    {
        return ['translation', 'translation:cache'];
    }

    /**
     * Reports whether exists.
     */
    public function exists(): bool
    {
        return $this->cache->exists();
    }

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->cache->path();
    }

    /**
     * Performs the rebuild operation.
     */
    public function rebuild(): void
    {
        $this->cache->write();
    }
}
