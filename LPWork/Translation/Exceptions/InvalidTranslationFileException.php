<?php

declare(strict_types=1);

namespace LPWork\Translation\Exceptions;

use RuntimeException;

/**
 * Reports invalid translation file exception failures.
 */
final class InvalidTranslationFileException extends RuntimeException
{
    /**
     * Creates a new InvalidTranslationFileException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Translation file is invalid: %s.', $path));
    }
}
