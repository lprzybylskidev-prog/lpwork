<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;

/**
 * Resolves http request format resolver values into runtime objects.
 */
final readonly class HttpRequestFormatResolver
{
    /**
     * Creates a new HttpRequestFormatResolver instance.
     */
    public function __construct(private AcceptHeaderParser $accept = new AcceptHeaderParser()) {}

    /**
     * Reports whether has json body.
     */
    public function hasJsonBody(HttpRequest $request): bool
    {
        return $this->isJsonMediaType($request->header('Content-Type'));
    }

    /**
     * Reports whether expects json.
     */
    public function expectsJson(HttpRequest $request, bool $api = false): bool
    {
        return $api || $this->acceptsJson($request);
    }

    /**
     * Performs the response format operation.
     */
    public function responseFormat(HttpRequest $request, bool $api = false): ResponseFormat
    {
        return $this->expectsJson($request, $api) ? ResponseFormat::Json : ResponseFormat::Html;
    }

    private function acceptsJson(HttpRequest $request): bool
    {
        $acceptedTypes = $this->accept->parse($request->header('Accept'));

        if ($this->isAjaxRequest($request) && $this->acceptsAnyResponse($acceptedTypes)) {
            return true;
        }

        foreach ($acceptedTypes as $accepted) {
            if ($accepted->isJson()) {
                return true;
            }

            if ($accepted->isHtml()) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param list<AcceptedMediaType> $acceptedTypes
     */
    private function acceptsAnyResponse(array $acceptedTypes): bool
    {
        if ($acceptedTypes === []) {
            return true;
        }

        foreach ($acceptedTypes as $accepted) {
            if ($accepted->isWildcard()) {
                return true;
            }
        }

        return false;
    }

    private function isAjaxRequest(HttpRequest $request): bool
    {
        return strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }

    private function isJsonMediaType(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        foreach (explode(',', strtolower($value)) as $part) {
            $mediaType = trim(explode(';', $part, 2)[0]);

            if ($mediaType === 'application/json' || str_ends_with($mediaType, '+json') || str_ends_with($mediaType, '/json')) {
                return true;
            }
        }

        return false;
    }
}
