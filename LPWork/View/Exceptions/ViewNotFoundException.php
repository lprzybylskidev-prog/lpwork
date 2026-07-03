<?php

declare(strict_types=1);

namespace LPWork\View\Exceptions;

use RuntimeException;

/**
 * Reports view not found exception failures.
 */
final class ViewNotFoundException extends RuntimeException
{
    /**
     * @param list<string> $paths
     */
    public static function forName(string $name, array $paths): self
    {
        return new self(sprintf(
            'View [%s] was not found in configured paths: %s.',
            $name,
            implode(', ', $paths),
        ));
    }
}
