<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use JsonException;
use LPWork\Http\HttpRequestFormatResolver;
use LPWork\Kernels\Http\Exceptions\MalformedJsonRequestBodyException;
use LPWork\Requests\HttpRequest;

/**
 * Represents the json request body parser framework component.
 */
final readonly class JsonRequestBodyParser
{
    /**
     * Creates a new JsonRequestBodyParser instance.
     */
    public function __construct(private HttpRequestFormatResolver $formatResolver = new HttpRequestFormatResolver()) {}

    /**
     * Builds or returns parse.
     */
    public function parse(HttpRequest $request): HttpRequest
    {
        if (!$this->formatResolver->hasJsonBody($request) || trim($request->body()) === '') {
            return $request;
        }

        try {
            $decoded = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw MalformedJsonRequestBodyException::forPrevious($exception);
        }

        $input = $this->stringKeyedArray($decoded);

        if ($input === null) {
            throw MalformedJsonRequestBodyException::forNonObjectBody();
        }

        return $request->withInput($input);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stringKeyedArray(mixed $data): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        $stringKeyed = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                return null;
            }

            $stringKeyed[$key] = $value;
        }

        return $stringKeyed;
    }
}
