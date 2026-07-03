<?php

declare(strict_types=1);

namespace LPWork\Responses\Exceptions;

use RuntimeException;

/**
 * Reports file response read exception failures.
 */
final class FileResponseReadException extends RuntimeException
{
    /**
     * Creates a new FileResponseReadException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Could not read response file: %s.', $path));
    }
}
