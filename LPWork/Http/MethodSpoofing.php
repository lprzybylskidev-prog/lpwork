<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Http\Exceptions\BadRequestException;
use LPWork\Http\Exceptions\InvalidSpoofedMethodException;

/**
 * Represents the method spoofing framework component.
 */
final class MethodSpoofing
{
    /**
     * @var list<string>
     */
    private const array SPOOFABLE_METHODS = ['PUT', 'PATCH', 'DELETE'];

    /**
     * Returns parsed input data from this boundary.
     */
    public static function input(string $method): string
    {
        $method = strtoupper(trim($method));

        if (!in_array($method, self::SPOOFABLE_METHODS, true)) {
            throw new InvalidSpoofedMethodException($method);
        }

        return sprintf('<input type="hidden" name="_method" value="%s">', $method);
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function resolve(string $method, array $input): string
    {
        $method = strtoupper(trim($method));

        if ($method !== 'POST' || !array_key_exists('_method', $input)) {
            return $method;
        }

        $spoofedMethod = $input['_method'];

        if (!is_scalar($spoofedMethod)) {
            throw new BadRequestException('Invalid HTTP method override.');
        }

        $spoofedMethod = strtoupper(trim((string) $spoofedMethod));

        if (!in_array($spoofedMethod, self::SPOOFABLE_METHODS, true)) {
            throw new BadRequestException('Invalid HTTP method override.');
        }

        return $spoofedMethod;
    }
}
