<?php

declare(strict_types=1);

namespace LPWork\Foundation;

/**
 * Represents the framework metadata framework component.
 */
final readonly class FrameworkMetadata
{
    public const string VERSION = '0.1.0-dev';

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
