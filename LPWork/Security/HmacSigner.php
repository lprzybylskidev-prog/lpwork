<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Security\Contracts\Signer;

/**
 * Represents the hmac signer framework component.
 */
final readonly class HmacSigner implements Signer
{
    /**
     * Creates a new HmacSigner instance.
     */
    public function __construct(
        private ApplicationKey $key,
    ) {}

    /**
     * Performs sign.
     */
    public function sign(string $value): string
    {
        return hash_hmac('sha256', $value, $this->key->bytes());
    }

    /**
     * Performs verify.
     */
    public function verify(string $value, string $signature): bool
    {
        return hash_equals($this->sign($value), $signature);
    }
}
