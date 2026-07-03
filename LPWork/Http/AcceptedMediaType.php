<?php

declare(strict_types=1);

namespace LPWork\Http;

/**
 * Represents the accepted media type framework component.
 */
final readonly class AcceptedMediaType
{
    /**
     * Creates a new AcceptedMediaType instance.
     */
    public function __construct(
        private string $type,
        private string $subtype,
        private float $quality,
        private int $position,
    ) {}

    /**
     * Performs the quality operation.
     */
    public function quality(): float
    {
        return $this->quality;
    }

    /**
     * Performs the position operation.
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * Reports whether matches.
     */
    public function matches(string $mediaType): bool
    {
        [$type, $subtype] = explode('/', strtolower($mediaType), 2);

        if ($this->type !== '*' && $this->type !== $type) {
            return false;
        }

        return $this->subtype === '*'
            || $this->subtype === $subtype
            || ($this->subtype[0] === '*' && str_ends_with($subtype, substr($this->subtype, 1)));
    }

    /**
     * Reports whether is json.
     */
    public function isJson(): bool
    {
        return $this->type !== '*'
            && $this->subtype !== '*'
            && ($this->subtype === 'json' || str_ends_with($this->subtype, '+json'));
    }

    /**
     * Reports whether is html.
     */
    public function isHtml(): bool
    {
        return ($this->type === 'text' && $this->subtype === 'html')
            || ($this->type === 'application' && $this->subtype === 'xhtml+xml');
    }

    /**
     * Reports whether is wildcard.
     */
    public function isWildcard(): bool
    {
        return $this->type === '*' && $this->subtype === '*';
    }
}
