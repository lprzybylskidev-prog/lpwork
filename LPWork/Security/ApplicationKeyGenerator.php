<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Security\Exceptions\ApplicationKeyGenerationException;
use Random\RandomException;

/**
 * Represents the application key generator framework component.
 */
final readonly class ApplicationKeyGenerator
{
    /**
     * Builds or returns generate.
     */
    public function generate(): string
    {
        try {
            return 'base64:' . base64_encode(random_bytes(32));
        } catch (RandomException $exception) {
            throw ApplicationKeyGenerationException::forRandomFailure($exception);
        }
    }
}
