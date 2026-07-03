<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Http\HttpRequestFormatResolver;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;
use LPWork\Routing\RouteMatch;

/**
 * Resolves http response format resolver values into runtime objects.
 */
final readonly class HttpResponseFormatResolver
{
    /**
     * Creates a new HttpResponseFormatResolver instance.
     */
    public function __construct(private HttpRequestFormatResolver $requestFormatResolver = new HttpRequestFormatResolver()) {}

    /**
     * Resolves configured input into a runtime value.
     */
    public function resolve(HttpRequest $request, ?RouteMatch $match = null): ResponseFormat
    {
        return $this->requestFormatResolver->responseFormat($request, $match?->route()->isApi() === true);
    }
}
