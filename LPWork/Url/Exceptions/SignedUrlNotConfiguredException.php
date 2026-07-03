<?php

declare(strict_types=1);

namespace LPWork\Url\Exceptions;

use RuntimeException;

/**
 * Reports signed url not configured exception failures.
 */
final class SignedUrlNotConfiguredException extends RuntimeException
{
    /**
     * Creates a new SignedUrlNotConfiguredException instance.
     */
    public function __construct()
    {
        parent::__construct('Signed URL support is not configured.');
    }
}
