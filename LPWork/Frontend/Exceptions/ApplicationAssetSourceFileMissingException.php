<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

/**
 * Reports application asset source file missing exception failures.
 */
final class ApplicationAssetSourceFileMissingException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetSourceFileMissingException instance.
     */
    public function __construct(string $entry, string $path)
    {
        parent::__construct("Application asset entry [{$entry}] points to missing source file [{$path}]. Create the file or update the asset entry declaration.");
    }
}
