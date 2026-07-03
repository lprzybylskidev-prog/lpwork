<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use InvalidArgumentException;

/**
 * Reports unsupported completion shell exception failures.
 */
final class UnsupportedCompletionShellException extends InvalidArgumentException
{
    /**
     * @param list<string> $supportedShells
     */
    public static function forShell(string $shell, array $supportedShells): self
    {
        return new self(sprintf(
            'Unsupported shell [%s]. Supported shells: %s.',
            $shell,
            implode(', ', $supportedShells),
        ));
    }
}
