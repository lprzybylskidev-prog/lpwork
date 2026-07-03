<?php

declare(strict_types=1);

namespace LPWork\Security\Contracts;

/**
 * Defines the contract for signer.
 */
interface Signer
{
    /**
     * Performs sign.
     */
    public function sign(string $value): string;

    /**
     * Performs verify.
     */
    public function verify(string $value, string $signature): bool;
}
