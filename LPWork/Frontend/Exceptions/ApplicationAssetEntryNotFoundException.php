<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

/**
 * Reports application asset entry not found exception failures.
 */
final class ApplicationAssetEntryNotFoundException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetEntryNotFoundException instance.
     */
    public function __construct(string $name)
    {
        parent::__construct("Application asset entry [{$name}] is not registered. Add it through an application asset entry provider.");
    }
}
