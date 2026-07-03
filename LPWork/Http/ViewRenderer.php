<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Responses\HttpResponse;
use LPWork\View\ViewFactory;

/**
 * Renders view renderer output.
 */
final readonly class ViewRenderer
{
    /**
     * Creates a new ViewRenderer instance.
     */
    public function __construct(private ViewFactory $views) {}

    /**
     * @param array<string, mixed>|object $data
     * @param array<string, string> $headers
     */
    public function render(string $view, array|object $data = [], int $statusCode = 200, array $headers = []): HttpResponse
    {
        return HttpResponse::html($this->views->render($view, $data), $statusCode, $headers);
    }
}
