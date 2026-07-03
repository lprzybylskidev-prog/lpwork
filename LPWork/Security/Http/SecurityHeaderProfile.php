<?php

declare(strict_types=1);

namespace LPWork\Security\Http;

/**
 * Represents the security header profile framework component.
 */
final readonly class SecurityHeaderProfile
{
    /**
     * @return array<string, string>
     */
    public static function default(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];
    }
}
