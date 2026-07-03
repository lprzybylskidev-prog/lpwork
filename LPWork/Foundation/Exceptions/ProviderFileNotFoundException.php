<?php

declare(strict_types=1);

namespace LPWork\Foundation\Exceptions;

use RuntimeException;

/**
 * Reports provider file not found exception failures.
 */
final class ProviderFileNotFoundException extends RuntimeException
{
    /**
     * Creates a new ProviderFileNotFoundException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Provider file does not exist: %s', $path));
    }
}
