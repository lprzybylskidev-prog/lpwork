<?php

declare(strict_types=1);

use LPWork\Shared\Http\HttpResponseHeaderParser;

it('parses status and response headers from PHP stream metadata', function (): void {
    $headers = new HttpResponseHeaderParser()->parse([
        'HTTP/1.1 201 Created',
        'Content-Type: application/json',
        'X-Trace-Id: abc:123',
    ]);

    expect($headers->status)->toBe(201)
        ->and($headers->headers)->toBe([
            'Content-Type' => 'application/json',
            'X-Trace-Id' => 'abc:123',
        ]);
});

it('returns an empty response header set when no status line is present', function (): void {
    $headers = new HttpResponseHeaderParser()->parse([]);

    expect($headers->status)->toBe(0)
        ->and($headers->headers)->toBe([]);
});
