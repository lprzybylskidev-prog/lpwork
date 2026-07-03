<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

/**
 * Reports application asset built file missing exception failures.
 */
final class ApplicationAssetBuiltFileMissingException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetBuiltFileMissingException instance.
     */
    public function __construct(string $asset, string $path)
    {
        parent::__construct("Built application asset [{$asset}] does not exist at [{$path}]. Run php lpwork frontend:build.");
    }
}
