<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Security\Exceptions\InvalidApplicationKeyException;

/**
 * Represents the application key framework component.
 */
final readonly class ApplicationKey
{
    private const PREFIX = 'base64:';

    private const MINIMUM_BYTES = 32;

    private function __construct(
        private string $bytes,
    ) {}

    /**
     * Creates a ApplicationKey instance from from string input.
     */
    public static function fromString(string $key): self
    {
        $key = trim($key);

        if ($key === '') {
            throw InvalidApplicationKeyException::empty();
        }

        if (str_starts_with($key, self::PREFIX)) {
            return self::fromBase64(substr($key, strlen(self::PREFIX)));
        }

        self::ensureMinimumLength($key);

        return new self($key);
    }

    /**
     * Performs the bytes operation.
     */
    public function bytes(): string
    {
        return $this->bytes;
    }

    private static function fromBase64(string $encoded): self
    {
        $decoded = base64_decode($encoded, strict: true);

        if ($decoded === false) {
            throw InvalidApplicationKeyException::malformedBase64();
        }

        self::ensureMinimumLength($decoded);

        return new self($decoded);
    }

    private static function ensureMinimumLength(string $bytes): void
    {
        if (strlen($bytes) < self::MINIMUM_BYTES) {
            throw InvalidApplicationKeyException::tooShort(self::MINIMUM_BYTES);
        }
    }
}
