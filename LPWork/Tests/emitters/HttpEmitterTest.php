<?php

declare(strict_types=1);

use LPWork\Emitters\Exceptions\UnsupportedResponseException;
use LPWork\Emitters\HttpEmitter;
use LPWork\Http\Cookie;
use LPWork\Responses\ConsoleResponse;
use LPWork\Responses\HttpResponse;

it('emits Http responses to status headers and output', function (): void {
    $output = fopen('php://memory', 'r+');
    $headers = [];
    $statusCode = null;

    if ($output === false) {
        throw new RuntimeException('Could not open memory stream.');
    }

    $emitter = new HttpEmitter(
        output: $output,
        headerSender: static function (string $header) use (&$headers): void {
            $headers[] = $header;
        },
        statusSender: static function (int $status) use (&$statusCode): void {
            $statusCode = $status;
        },
    );

    $exitCode = $emitter->emit(new HttpResponse(
        body: 'Created',
        statusCode: 201,
        headers: [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Location' => '/articles/42',
        ],
    ));

    rewind($output);

    expect($exitCode)->toBe(201)
        ->and($statusCode)->toBe(201)
        ->and($headers)->toBe([
            'Content-Type: text/plain; charset=UTF-8',
            'Location: /articles/42',
        ])
        ->and(stream_get_contents($output))->toBe('Created');
});

it('rejects non Http responses', function (): void {
    $output = fopen('php://memory', 'r+');

    if ($output === false) {
        throw new RuntimeException('Could not open memory stream.');
    }

    $emitter = new HttpEmitter(
        output: $output,
        headerSender: static function (string $header): void {},
        statusSender: static function (int $statusCode): void {},
    );

    $emitter->emit(ConsoleResponse::output('Hello'));
})->throws(UnsupportedResponseException::class, 'Http emitter cannot emit LPWork\Responses\ConsoleResponse.');

it('emits cookies and streamed HTTP response bodies', function (): void {
    $output = fopen('php://memory', 'r+');
    $headers = [];
    $statusCode = null;

    if ($output === false) {
        throw new RuntimeException('Could not open memory stream.');
    }

    $emitter = new HttpEmitter(
        output: $output,
        headerSender: static function (string $header) use (&$headers): void {
            $headers[] = $header;
        },
        statusSender: static function (int $status) use (&$statusCode): void {
            $statusCode = $status;
        },
    );

    $response = HttpResponse::stream(static function (mixed $output): void {
        fwrite($output, 'streamed');
    }, headers: ['Content-Type' => 'text/plain'])
        ->withCookie(new Cookie('theme', 'dark'));

    expect($emitter->emit($response))->toBe(200);

    rewind($output);

    expect($statusCode)->toBe(200)
        ->and($headers)->toBe([
            'Content-Type: text/plain',
            'Set-Cookie: theme=dark; Path=/; SameSite=Lax; HttpOnly',
        ])
        ->and(stream_get_contents($output))->toBe('streamed');
});
