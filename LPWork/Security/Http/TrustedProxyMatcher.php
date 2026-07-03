<?php

declare(strict_types=1);

namespace LPWork\Security\Http;

/**
 * Represents the trusted proxy matcher framework component.
 */
final readonly class TrustedProxyMatcher
{
    /**
     * @param list<string> $proxies
     */
    public function __construct(private array $proxies) {}

    /**
     * Reports whether matches.
     */
    public function matches(string $ip): bool
    {
        foreach ($this->proxies as $proxy) {
            if ($proxy === '*' || $proxy === $ip || $this->matchesCidr($ip, $proxy)) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        [$range, $prefix] = explode('/', $cidr, 2);

        if (!ctype_digit($prefix)) {
            return false;
        }

        $ipBytes = inet_pton($ip);
        $rangeBytes = inet_pton($range);

        if ($ipBytes === false || $rangeBytes === false || strlen($ipBytes) !== strlen($rangeBytes)) {
            return false;
        }

        $prefixLength = (int) $prefix;
        $maxPrefixLength = strlen($ipBytes) * 8;

        if ($prefixLength < 0 || $prefixLength > $maxPrefixLength) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($ipBytes, 0, $fullBytes) !== substr($rangeBytes, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($ipBytes[$fullBytes]) & $mask) === (ord($rangeBytes[$fullBytes]) & $mask);
    }
}
