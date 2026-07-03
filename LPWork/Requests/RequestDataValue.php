<?php

declare(strict_types=1);

namespace LPWork\Requests;

/**
 * Represents the request data value framework component.
 */
final readonly class RequestDataValue
{
    /**
     * Creates a new RequestDataValue instance.
     */
    public function __construct(
        public bool $found,
        public mixed $value = null,
    ) {}
}
