<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\Frontend\FrameworkPageRenderer;
use LPWork\Http\Contracts\HttpException;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Renders http production exception renderer output.
 */
final readonly class HttpProductionExceptionRenderer implements HttpExceptionRenderer
{
    /**
     * Creates a new HttpProductionExceptionRenderer instance.
     */
    public function __construct(
        private FrameworkPageRenderer $pages = new FrameworkPageRenderer(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): HttpResponse
    {
        $statusCode = $throwable instanceof HttpException ? $throwable->statusCode() : 500;
        $headers = $throwable instanceof HttpException ? $throwable->headers() : [];

        return HttpResponse::html(
            $this->pages->errorPage(
                title: (string) $statusCode,
                kicker: 'Production response',
                heading: $statusCode >= 500 ? 'Server error' : 'Request rejected',
                statusCode: $statusCode,
                message: 'The request could not be completed. Error details are hidden in production.',
                variant: 'error',
            ),
            statusCode: $statusCode,
            headers: $headers,
        );
    }
}
