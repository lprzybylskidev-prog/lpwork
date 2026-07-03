<?php

declare(strict_types=1);

namespace LPWork\Maintenance;

use LPWork\Frontend\FrameworkPageRenderer;
use LPWork\Kernels\Http\ControllerDispatcher;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
use Throwable;

/**
 * Renders maintenance page renderer output.
 */
final readonly class MaintenancePageRenderer
{
    /**
     * Creates a new MaintenancePageRenderer instance.
     */
    public function __construct(
        private FrameworkPageRenderer $pages = new FrameworkPageRenderer(),
        private ?Router $router = null,
        private ?ControllerDispatcher $dispatcher = null,
        private ?string $route = null,
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(MaintenanceState $state): HttpResponse
    {
        $headers = [];

        $retryAfter = $state->retryAfter();

        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }

        if ($this->router !== null && $this->dispatcher !== null && $this->route !== null) {
            try {
                $request = HttpRequest::fromArrays([
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => $this->route,
                ]);
                $response = $this->dispatcher->dispatch($request, $this->router->routes()->match('GET', $request->path()))
                    ->withStatus(503);

                foreach ($headers as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $response;
            } catch (Throwable) {
                // The built-in dark framework page remains the safe fallback when an app override fails.
            }
        }

        return HttpResponse::html(
            $this->pages->errorPage(
                title: '503 Service unavailable',
                kicker: 'Maintenance mode',
                heading: 'Service unavailable',
                statusCode: 503,
                message: 'The application is temporarily unavailable while maintenance mode is active.',
                variant: 'maintenance',
            ),
            statusCode: 503,
            headers: $headers,
        );
    }
}
