<?php

declare(strict_types=1);

namespace LPWork\Http;

use function array_filter;
use function array_map;
use function explode;
use function is_numeric;
use function strtolower;
use function trim;
use function usort;

/**
 * Represents the accept header parser framework component.
 */
final readonly class AcceptHeaderParser
{
    /**
     * @return list<AcceptedMediaType>
     */
    public function parse(?string $header): array
    {
        if ($header === null || trim($header) === '') {
            return [];
        }

        $accepted = [];

        foreach (explode(',', $header) as $position => $part) {
            $mediaType = $this->parsePart($part, $position);

            if ($mediaType !== null && $mediaType->quality() > 0.0) {
                $accepted[] = $mediaType;
            }
        }

        usort($accepted, static function (AcceptedMediaType $left, AcceptedMediaType $right): int {
            if ($left->quality() === $right->quality()) {
                return $left->position() <=> $right->position();
            }

            return $left->quality() < $right->quality() ? 1 : -1;
        });

        return $accepted;
    }

    private function parsePart(string $part, int $position): ?AcceptedMediaType
    {
        $segments = array_map(trim(...), array_filter(explode(';', $part), static fn(string $segment): bool => trim($segment) !== ''));
        $media = strtolower($segments[0] ?? '');

        if (!str_contains($media, '/')) {
            return null;
        }

        [$type, $subtype] = explode('/', $media, 2);
        $quality = 1.0;

        foreach ($segments as $segment) {
            if (!str_starts_with(strtolower($segment), 'q=')) {
                continue;
            }

            $rawQuality = substr($segment, 2);
            $quality = is_numeric($rawQuality) ? max(0.0, min(1.0, (float) $rawQuality)) : 0.0;
        }

        return new AcceptedMediaType($type, $subtype, $quality, $position);
    }
}
