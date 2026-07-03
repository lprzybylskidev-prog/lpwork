<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

/**
 * Reports application asset manifest entry not found exception failures.
 */
final class ApplicationAssetManifestEntryNotFoundException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetManifestEntryNotFoundException instance.
     */
    public function __construct(string $sourcePath)
    {
        parent::__construct("Application asset manifest does not contain source entry [{$sourcePath}]. Run php lpwork frontend:build.");
    }
}
