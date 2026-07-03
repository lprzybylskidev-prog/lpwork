<?php

declare(strict_types=1);

namespace LPWork\Queue\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * Reports invalid queued job exception failures.
 */
final class InvalidQueuedJobException extends InvalidArgumentException
{
    /**
     * Performs the unserializable operation.
     */
    public static function unserializable(string $class, Throwable $previous): self
    {
        return new self(sprintf('Queued job could not be serialized: %s.', $class), previous: $previous);
    }

    /**
     * Reports whether cannot restore.
     */
    public static function cannotRestore(string $class): self
    {
        return new self(sprintf('Queued job could not be restored as an object of class: %s.', $class));
    }

    /**
     * Reports whether missing handler.
     */
    public static function missingHandler(string $class): self
    {
        return new self(sprintf('Queued job must define a handle method: %s.', $class));
    }
}
