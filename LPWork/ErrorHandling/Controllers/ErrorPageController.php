<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Controllers;

use LPWork\Frontend\FrameworkPageRenderer;
use LPWork\Responses\HttpResponse;

/**
 * Handles error page controller HTTP requests.
 */
final readonly class ErrorPageController
{
    /**
     * Creates a new ErrorPageController instance.
     */
    public function __construct(
        private FrameworkPageRenderer $pages = new FrameworkPageRenderer(),
    ) {}

    /**
     * Performs the show operation.
     */
    public function show(string $code): HttpResponse
    {
        $statusCode = ctype_digit($code) ? (int) $code : 500;
        $title = $this->title($statusCode);
        $message = $this->message($statusCode);

        return HttpResponse::html($this->pages->errorPage(
            title: $statusCode . ' ' . $title,
            kicker: 'Production response',
            heading: $title,
            statusCode: $statusCode,
            message: $message,
            variant: 'error',
        ));
    }

    private function title(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Page not found',
            405 => 'Method not allowed',
            419 => 'Page expired',
            429 => 'Too many requests',
            500 => 'Server error',
            503 => 'Service unavailable',
            default => $statusCode >= 500 ? 'Server error' : 'Request error',
        };
    }

    private function message(int $statusCode): string
    {
        return match ($statusCode) {
            404 => 'The page you are looking for could not be found.',
            405 => 'This request method is not allowed for the requested page.',
            429 => 'Too many requests were sent in a short period of time.',
            default => $statusCode >= 500
                ? 'The request could not be completed. Error details are hidden in production.'
                : 'The request could not be completed.',
        };
    }

}
