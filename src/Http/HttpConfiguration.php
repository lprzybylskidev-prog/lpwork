<?php
declare(strict_types=1);

namespace LPwork\Http;

/**
 * Typed configuration for HTTP helpers and middleware.
 */
final class HttpConfiguration
{
    /**
     * @var bool
     */
    private bool $bodyParsingEnabled;

    /**
     * @var int
     */
    private int $maxBodySize;

    /**
     * @var bool
     */
    private bool $rejectInvalidJson;

    /**
     * @var array<int, string>
     */
    private array $allowedContentTypes;

    /**
     * @var int
     */
    private int $jsonMaxDepth;

    /**
     * @var bool
     */
    private bool $jsonAssoc;

    /**
     * @var bool
     */
    private bool $corsEnabled;

    /**
     * @var array<int, string>
     */
    private array $corsAllowOrigin;

    /**
     * @var array<int, string>
     */
    private array $corsAllowMethods;

    /**
     * @var array<int, string>
     */
    private array $corsAllowHeaders;

    /**
     * @var array<int, string>
     */
    private array $corsExposeHeaders;

    /**
     * @var bool
     */
    private bool $corsAllowCredentials;

    /**
     * @var int
     */
    private int $corsMaxAge;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $bodyParsing = (array) ($config['body_parsing'] ?? []);
        $cors = (array) ($config['cors'] ?? []);

        $this->bodyParsingEnabled = (bool) ($bodyParsing['enabled'] ?? true);
        $this->maxBodySize = (int) ($bodyParsing['max_body_size'] ?? 0);
        $this->rejectInvalidJson = (bool) ($bodyParsing['reject_invalid_json'] ?? true);
        $this->allowedContentTypes = \array_values((array) ($bodyParsing['allowed_types'] ?? []));
        $jsonConfig = (array) ($bodyParsing['json'] ?? []);
        $this->jsonMaxDepth = (int) ($jsonConfig['max_depth'] ?? 512);
        $this->jsonAssoc = (bool) ($jsonConfig['assoc'] ?? false);

        $this->corsEnabled = (bool) ($cors['enabled'] ?? false);
        $this->corsAllowOrigin = $this->normalizeList($cors['allow_origin'] ?? ['*']);
        $this->corsAllowMethods = $this->normalizeCsvList(
            $cors['allow_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        );
        $this->corsAllowHeaders = $this->normalizeCsvList(
            $cors['allow_headers'] ?? ['Content-Type', 'Authorization'],
        );
        $this->corsExposeHeaders = $this->normalizeCsvList($cors['expose_headers'] ?? []);
        $this->corsAllowCredentials = (bool) ($cors['allow_credentials'] ?? false);
        $this->corsMaxAge = (int) ($cors['max_age'] ?? 0);
    }

    /**
     * @return bool
     */
    public function bodyParsingEnabled(): bool
    {
        return $this->bodyParsingEnabled;
    }

    /**
     * @return int
     */
    public function maxBodySize(): int
    {
        return $this->maxBodySize;
    }

    /**
     * @return bool
     */
    public function rejectInvalidJson(): bool
    {
        return $this->rejectInvalidJson;
    }

    /**
     * @return array<int, string>
     */
    public function allowedContentTypes(): array
    {
        return $this->allowedContentTypes;
    }

    /**
     * @return int
     */
    public function jsonMaxDepth(): int
    {
        if ($this->jsonMaxDepth < 1) {
            return 1;
        }

        return $this->jsonMaxDepth;
    }

    /**
     * @return bool
     */
    public function jsonAssoc(): bool
    {
        return $this->jsonAssoc;
    }

    /**
     * @return bool
     */
    public function corsEnabled(): bool
    {
        return $this->corsEnabled;
    }

    /**
     * @return array<int, string>
     */
    public function corsAllowOrigin(): array
    {
        return $this->corsAllowOrigin;
    }

    /**
     * @return array<int, string>
     */
    public function corsAllowMethods(): array
    {
        return $this->corsAllowMethods;
    }

    /**
     * @return array<int, string>
     */
    public function corsAllowHeaders(): array
    {
        return $this->corsAllowHeaders;
    }

    /**
     * @return array<int, string>
     */
    public function corsExposeHeaders(): array
    {
        return $this->corsExposeHeaders;
    }

    /**
     * @return bool
     */
    public function corsAllowCredentials(): bool
    {
        return $this->corsAllowCredentials;
    }

    /**
     * @return int
     */
    public function corsMaxAge(): int
    {
        return $this->corsMaxAge;
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
