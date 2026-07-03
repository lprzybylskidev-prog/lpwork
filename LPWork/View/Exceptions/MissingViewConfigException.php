<?php

declare(strict_types=1);

namespace LPWork\View\Exceptions;

use RuntimeException;

/**
 * Reports missing view config exception failures.
 */
final class MissingViewConfigException extends RuntimeException
{
    /**
     * Creates a new MissingViewConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('View configuration value is missing: %s.', $key));
    }
}
