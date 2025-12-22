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
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $bodyParsing = (array) ($config['body_parsing'] ?? []);

        $this->bodyParsingEnabled = (bool) ($bodyParsing['enabled'] ?? true);
        $this->maxBodySize = (int) ($bodyParsing['max_body_size'] ?? 0);
        $this->rejectInvalidJson = (bool) ($bodyParsing['reject_invalid_json'] ?? true);
        $this->allowedContentTypes = \array_values((array) ($bodyParsing['allowed_types'] ?? []));
        $jsonConfig = (array) ($bodyParsing['json'] ?? []);
        $this->jsonMaxDepth = (int) ($jsonConfig['max_depth'] ?? 512);
        $this->jsonAssoc = (bool) ($jsonConfig['assoc'] ?? false);
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
}
