<?php

declare(strict_types=1);

namespace LPWork\Shared\Exceptions;

use RuntimeException;

/**
 * Reports missing php extension exception failures.
 */
final class MissingPhpExtensionException extends RuntimeException
{
    /**
     * Creates a new MissingPhpExtensionException instance.
     */
    public function __construct(string $extension, string $feature)
    {
        parent::__construct(sprintf('PHP extension [%s] is required for [%s].', $extension, $feature));
    }
}
