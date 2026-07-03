<?php

declare(strict_types=1);

namespace LPWork\Container\Exceptions;

use RuntimeException;

/**
 * Reports circular dependency exception failures.
 */
final class CircularDependencyException extends RuntimeException
{
    /**
     * @param non-empty-list<string> $chain
     */
    public static function fromChain(array $chain): self
    {
        return new self(sprintf(
            'Circular dependency detected while resolving [%s]: %s.',
            $chain[0],
            implode(' -> ', $chain),
        ));
    }
}
