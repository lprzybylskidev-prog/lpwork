<?php
declare(strict_types=1);

namespace LPwork\Http;

use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration for HTTP helpers and middleware.
 */
final class HttpConfiguration
{
    use ConfigNormalizer;

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
        // Support flattened config (current) and legacy nested "body_parsing".
        $bodyParsing = (array) ($config['body_parsing'] ?? $config);

        $this->bodyParsingEnabled = $this->boolVal(
            $bodyParsing['enabled'] ?? null,
            'http.body_parsing.enabled',
            true,
        );
        $this->maxBodySize = $this->intVal(
            $bodyParsing['max_body_size'] ?? null,
            'http.body_parsing.max_body_size',
            0,
            0,
        );
        $this->rejectInvalidJson = $this->boolVal(
            $bodyParsing['reject_invalid_json'] ?? null,
            'http.body_parsing.reject_invalid_json',
            true,
        );
        $this->allowedContentTypes = $this->stringList(
            $bodyParsing['allowed_types'] ?? [],
            'http.body_parsing.allowed_types',
        );
        $jsonConfig = (array) ($bodyParsing['json'] ?? []);
        $this->jsonMaxDepth = $this->intVal(
            $jsonConfig['max_depth'] ?? null,
            'http.body_parsing.json.max_depth',
            512,
            1,
        );
        $this->jsonAssoc = $this->boolVal(
            $jsonConfig['assoc'] ?? null,
            'http.body_parsing.json.assoc',
            false,
        );
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
