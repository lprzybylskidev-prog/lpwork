<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\Http\Contracts\HttpException;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Renders json http exception renderer output.
 */
final readonly class JsonHttpExceptionRenderer implements HttpExceptionRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): HttpResponse
    {
        $statusCode = $throwable instanceof HttpException ? $throwable->statusCode() : 500;
        $headers = $throwable instanceof HttpException ? $throwable->headers() : [];

        return HttpResponse::json([
            'error' => [
                'status' => $statusCode,
                'message' => $this->message($statusCode),
            ],
        ], statusCode: $statusCode, headers: $headers);
    }

    private function message(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            410 => 'Gone',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            503 => 'Service Unavailable',
            default => 'Internal Server Error',
        };
    }
}
