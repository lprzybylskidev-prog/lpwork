<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

use Throwable;

/**
 * Reports application asset manifest invalid exception failures.
 */
final class ApplicationAssetManifestInvalidException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetManifestInvalidException instance.
     */
    public function __construct(string $path, ?Throwable $previous = null)
    {
        parent::__construct("Application asset manifest at [{$path}] is invalid. Rebuild it with php lpwork frontend:build.", previous: $previous);
    }
}
