<?php

declare(strict_types=1);

namespace LPWork\Shared\Http;

/**
 * Represents the http response header parser framework component.
 */
final readonly class HttpResponseHeaderParser
{
    /**
     * @param list<string> $lines
     */
    public function parse(array $lines): HttpResponseHeaders
    {
        $status = 0;
        $headers = [];

        foreach ($lines as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $matches) === 1) {
                $status = (int) $matches[1];

                continue;
            }

            $parts = explode(':', $line, 2);

            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        return new HttpResponseHeaders($status, $headers);
    }
}
