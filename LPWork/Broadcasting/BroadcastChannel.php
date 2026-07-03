<?php

declare(strict_types=1);

namespace LPWork\Broadcasting;

use Closure;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastChannelException;

/**
 * Represents the broadcast channel framework component.
 */
final readonly class BroadcastChannel
{
    /**
     * @param null|Closure(mixed): bool $authorizer
     */
    public function __construct(
        private string $name,
        private bool $private = false,
        private ?Closure $authorizer = null,
    ) {
        if ($this->name === '') {
            throw InvalidBroadcastChannelException::emptyName();
        }
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Reports whether is private.
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * Reports whether allows.
     */
    public function allows(mixed $subject): bool
    {
        if ($this->authorizer === null) {
            return !$this->private;
        }

        return ($this->authorizer)($subject);
    }
}
