<?php

declare(strict_types=1);

namespace LPWork\Foundation;

/**
 * Represents the framework metadata framework component.
 */
final readonly class FrameworkMetadata
{
    public const string VERSION = 'v1.0.1';

    /**
     * Creates a new FrameworkMetadata instance.
     */
    public function __construct(
        private string $version = self::VERSION,
    ) {}

    /**
     * Performs the version operation.
     */
    public function version(): string
    {
        return $this->version;
    }
}
