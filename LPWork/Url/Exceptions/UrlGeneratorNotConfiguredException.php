<?php

declare(strict_types=1);

namespace LPWork\Url\Exceptions;

use RuntimeException;

/**
 * Reports url generator not configured exception failures.
 */
final class UrlGeneratorNotConfiguredException extends RuntimeException
{
    /**
     * Creates a new UrlGeneratorNotConfiguredException instance.
     */
    public function __construct()
    {
        parent::__construct('URL generator is not configured.');
    }
}
