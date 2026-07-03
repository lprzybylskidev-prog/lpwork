<?php

declare(strict_types=1);

namespace LPWork\Security;

use DateTimeInterface;

use function http_build_query;
use function is_numeric;

use LPWork\Security\Contracts\Signer;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

use function parse_str;
use function parse_url;

use const PHP_QUERY_RFC3986;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;

/**
 * Represents the signed url framework component.
 */
final readonly class SignedUrl
{
    /**
     * Creates a new SignedUrl instance.
     */
    public function __construct(
        private Signer $signer,
        private Clock $clock = new SystemClock(),
    ) {}

    /**
     * Performs sign.
     */
    public function sign(string $url): string
    {
        return $this->appendQuery($url, [
            'signature' => $this->signer->sign($this->canonical($url)),
        ]);
    }

    /**
     * Performs the temporary operation.
     */
    public function temporary(string $url, DateTimeInterface|int $expires): string
    {
        $url = $this->appendQuery($url, [
            'expires' => $expires instanceof DateTimeInterface ? $expires->getTimestamp() : $expires,
        ]);

        return $this->sign($url);
    }

    /**
     * Performs verify.
     */
    public function verify(string $url): bool
    {
        $query = $this->query($url);
        $signature = $query['signature'] ?? null;

        if (!is_string($signature) || $signature === '') {
            return false;
        }

        if ($this->expired($query['expires'] ?? null)) {
            return false;
        }

        return $this->signer->verify($this->canonical($url), $signature);
    }

    private function expired(mixed $expires): bool
    {
        if ($expires === null) {
            return false;
        }

        if (!is_numeric($expires)) {
            return true;
        }

        return (int) $expires < $this->clock->now()->getTimestamp();
    }

    /**
     * @param array<string, mixed> $query
     */
    private function appendQuery(string $url, array $query): string
    {
        $merged = [
            ...$this->query($url),
            ...$query,
        ];

        return $this->base($url) . '?' . http_build_query($merged, encoding_type: PHP_QUERY_RFC3986);
    }

    private function canonical(string $url): string
    {
        $query = $this->query($url);

        unset($query['signature']);
        $this->sortQuery($query);

        $canonical = $this->base($url);

        if ($query !== []) {
            $canonical .= '?' . http_build_query($query, encoding_type: PHP_QUERY_RFC3986);
        }

        return $canonical;
    }

    /**
     * @return array<string, mixed>
     */
    private function query(string $url): array
    {
        $rawQuery = parse_url($url, PHP_URL_QUERY);

        if (!is_string($rawQuery) || $rawQuery === '') {
            return [];
        }

        parse_str($rawQuery, $query);

        return $this->normalizeQuery($query);
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function sortQuery(array &$query): void
    {
        ksort($query);

        foreach ($query as &$value) {
            if (is_array($value)) {
                $this->sortQuery($value);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function normalizeQuery(array $query): array
    {
        $normalized = [];

        foreach ($query as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $normalized[$key] = is_array($value) ? $this->normalizeQuery($value) : $value;
        }

        return $normalized;
    }

    private function base(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $path = parse_url($url, PHP_URL_PATH);
        $base = '';

        if (is_string($scheme) && is_string($host)) {
            $base .= $scheme . '://' . $host;

            if (is_int($port)) {
                $base .= ':' . $port;
            }
        }

        $base .= is_string($path) && $path !== '' ? $path : '/';

        return $base;
    }
}
