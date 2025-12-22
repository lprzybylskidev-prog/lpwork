<?php
declare(strict_types=1);

namespace LPwork\Security;

/**
 * Typed configuration for CSRF protection and security headers.
 */
final class SecurityConfiguration
{
    /**
     * @var array<string, mixed>
     */
    private array $csrf;

    /**
     * @var array<string, mixed>
     */
    private array $headers;

    /**
     * @var array<string, mixed>
     */
    private array $cors;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->csrf = (array) ($config['csrf'] ?? []);
        $this->headers = (array) ($config['headers'] ?? []);
        $this->cors = (array) ($config['cors'] ?? []);
    }

    /**
     * @return bool
     */
    public function csrfEnabled(): bool
    {
        return (bool) ($this->csrf['enabled'] ?? true);
    }

    /**
     * @return string
     */
    public function csrfTokenId(): string
    {
        return (string) ($this->csrf['token_id'] ?? 'default');
    }

    /**
     * @return string
     */
    public function csrfHeader(): string
    {
        return (string) ($this->csrf['header'] ?? 'X-CSRF-Token');
    }

    /**
     * @return string
     */
    public function csrfParameter(): string
    {
        return (string) ($this->csrf['parameter'] ?? '_csrf');
    }

    /**
     * @return array<int, string>
     */
    public function csrfMethods(): array
    {
        $methods = (array) ($this->csrf['methods'] ?? ['POST', 'PUT', 'PATCH', 'DELETE']);

        return \array_values(
            \array_map(static fn(mixed $m): string => \strtoupper(\trim((string) $m)), $methods),
        );
    }

    /**
     * @return array<int, string>
     */
    public function csrfExcludePaths(): array
    {
        $paths = (array) ($this->csrf['exclude_paths'] ?? []);

        return \array_values(
            \array_filter(
                \array_map(static fn(mixed $p): string => \trim((string) $p), $paths),
                static fn(string $p): bool => $p !== '',
            ),
        );
    }

    /**
     * @return bool
     */
    public function headersEnabled(): bool
    {
        return (bool) ($this->headers['enabled'] ?? true);
    }

    /**
     * @return string
     */
    public function frameOptions(): string
    {
        return (string) ($this->headers['frame_options'] ?? 'SAMEORIGIN');
    }

    /**
     * @return string
     */
    public function referrerPolicy(): string
    {
        return (string) ($this->headers['referrer_policy'] ?? 'no-referrer');
    }

    /**
     * @return string
     */
    public function permissionsPolicy(): string
    {
        return (string) ($this->headers['permissions_policy'] ?? '');
    }

    /**
     * @return bool
     */
    public function contentTypeOptions(): bool
    {
        return (bool) ($this->headers['content_type_options'] ?? true);
    }

    /**
     * @return bool
     */
    public function corsEnabled(): bool
    {
        return (bool) ($this->cors['enabled'] ?? false);
    }

    /**
     * @return array<int, string>
     */
    public function corsAllowOrigin(): array
    {
        return $this->normalizeList($this->cors['allow_origin'] ?? ['*']);
    }

    /**
     * @return array<int, string>
     */
    public function corsAllowMethods(): array
    {
        return $this->normalizeCsvList(
            $this->cors['allow_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        );
    }

    /**
     * @return array<int, string>
     */
    public function corsAllowHeaders(): array
    {
        return $this->normalizeCsvList(
            $this->cors['allow_headers'] ?? ['Content-Type', 'Authorization'],
        );
    }

    /**
     * @return array<int, string>
     */
    public function corsExposeHeaders(): array
    {
        return $this->normalizeCsvList($this->cors['expose_headers'] ?? []);
    }

    /**
     * @return bool
     */
    public function corsAllowCredentials(): bool
    {
        return (bool) ($this->cors['allow_credentials'] ?? false);
    }

    /**
     * @return int
     */
    public function corsMaxAge(): int
    {
        return (int) ($this->cors['max_age'] ?? 0);
    }

    /**
     * @param mixed $value
     *
     * @return array<int, string>
     */
    private function normalizeList(mixed $value): array
    {
        $list = (array) $value;

        return \array_values(
            \array_filter(
                \array_map(static fn(mixed $item): string => \trim((string) $item), $list),
                static fn(string $item): bool => $item !== '',
            ),
        );
    }

    /**
     * @param mixed $value
     *
     * @return array<int, string>
     */
    private function normalizeCsvList(mixed $value): array
    {
        $items = [];

        foreach ((array) $value as $entry) {
            $parts = \array_map('trim', \explode(',', (string) $entry));

            foreach ($parts as $part) {
                if ($part !== '') {
                    $items[] = $part;
                }
            }
        }

        return \array_values($items);
    }
}
