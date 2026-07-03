<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid path exception failures.
 */
final class InvalidPathException extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Filesystem path cannot be empty.');
    }

    /**
     * Performs the contains null byte operation.
     */
    public static function containsNullByte(): self
    {
        return new self('Filesystem path cannot contain null bytes.');
    }

    /**
     * Performs the stream operation.
     */
    public static function stream(string $path): self
    {
        return new self("Filesystem path must reference a local path: {$path}.");
    }

    /**
     * Performs the absolute operation.
     */
    public static function absolute(string $path): self
    {
        return new self("Storage-relative path cannot be absolute: {$path}.");
    }

    /**
     * Performs the traversal operation.
     */
    public static function traversal(string $path): self
    {
        return new self("Storage-relative path cannot traverse directories: {$path}.");
    }
}
