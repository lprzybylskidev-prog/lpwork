<?php
declare(strict_types=1);

namespace LPwork\Security;

use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration for CSRF protection and security headers.
 */
final class SecurityConfiguration
{
    use ConfigNormalizer;

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
        $csrf = (array) ($config['csrf'] ?? []);
        $headers = (array) ($config['headers'] ?? []);
        $cors = (array) ($config['cors'] ?? []);

        $this->csrf = [
            'enabled' => $this->boolVal($csrf['enabled'] ?? null, 'security.csrf.enabled', true),
            'token_id' => $this->stringVal(
                $csrf['token_id'] ?? null,
                'security.csrf.token_id',
                'default',
                false,
            ),
            'header' => $this->stringVal(
                $csrf['header'] ?? null,
                'security.csrf.header',
                'X-CSRF-Token',
                false,
            ),
            'parameter' => $this->stringVal(
                $csrf['parameter'] ?? null,
                'security.csrf.parameter',
                '_csrf',
                false,
            ),
            'methods' => $this->stringList(
                $csrf['methods'] ?? ['POST', 'PUT', 'PATCH', 'DELETE'],
                'security.csrf.methods',
            ),
            'exclude_paths' => $this->stringList(
                $csrf['exclude_paths'] ?? [],
                'security.csrf.exclude_paths',
            ),
        ];

        $frameOptions = $this->stringVal(
            $headers['frame_options'] ?? null,
            'security.headers.frame_options',
            'SAMEORIGIN',
            false,
        );

        $this->headers = [
            'enabled' => $this->boolVal(
                $headers['enabled'] ?? null,
                'security.headers.enabled',
                true,
            ),
            'frame_options' => $frameOptions,
            'referrer_policy' => $this->stringVal(
                $headers['referrer_policy'] ?? null,
                'security.headers.referrer_policy',
                'no-referrer',
                false,
            ),
            'permissions_policy' => $this->stringVal(
                $headers['permissions_policy'] ?? null,
                'security.headers.permissions_policy',
                '',
                true,
            ),
            'content_type_options' => $this->boolVal(
                $headers['content_type_options'] ?? null,
                'security.headers.content_type_options',
                true,
            ),
        ];

        $this->cors = [
            'enabled' => $this->boolVal($cors['enabled'] ?? null, 'security.cors.enabled', false),
            'allow_origin' => $this->stringList(
                $cors['allow_origin'] ?? ['*'],
                'security.cors.allow_origin',
            ),
            'allow_methods' => \array_map(
                static fn(string $m): string => \strtoupper($m),
                $this->stringList(
                    $cors['allow_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                    'security.cors.allow_methods',
                ),
            ),
            'allow_headers' => $this->stringList(
                $cors['allow_headers'] ?? ['Content-Type', 'Authorization'],
                'security.cors.allow_headers',
            ),
            'expose_headers' => $this->stringList(
                $cors['expose_headers'] ?? [],
                'security.cors.expose_headers',
            ),
            'allow_credentials' => $this->boolVal(
                $cors['allow_credentials'] ?? null,
                'security.cors.allow_credentials',
                false,
            ),
            'max_age' => $this->intVal($cors['max_age'] ?? null, 'security.cors.max_age', 0, 0),
        ];
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
