<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

/**
 * Reports application asset manifest missing exception failures.
 */
final class ApplicationAssetManifestMissingException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetManifestMissingException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Application asset manifest does not exist at [{$path}]. Run php lpwork frontend:build.");
    }
}
