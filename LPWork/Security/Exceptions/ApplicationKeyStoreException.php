<?php

declare(strict_types=1);

namespace LPWork\Security\Exceptions;

use RuntimeException;

/**
 * Reports application key store exception failures.
 */
final class ApplicationKeyStoreException extends RuntimeException
{
    /**
     * Reports whether missing file.
     */
    public static function missingFile(string $path): self
    {
        return new self(sprintf('Environment file does not exist: %s.', $path));
    }

    /**
     * Performs the unreadable file operation.
     */
    public static function unreadableFile(string $path): self
    {
        return new self(sprintf('Environment file is not readable: %s.', $path));
    }

    /**
     * Performs the could not read operation.
     */
    public static function couldNotRead(string $path): self
    {
        return new self(sprintf('Environment file could not be read: %s.', $path));
    }

    /**
     * Performs the could not write operation.
     */
    public static function couldNotWrite(string $path): self
    {
        return new self(sprintf('Environment file could not be written: %s.', $path));
    }
}
