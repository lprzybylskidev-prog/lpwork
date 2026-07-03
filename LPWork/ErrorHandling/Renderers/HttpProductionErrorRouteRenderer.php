<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\Http\Contracts\HttpException;
use LPWork\Kernels\Http\ControllerDispatcher;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
use Throwable;

/**
 * Renders http production error route renderer output.
 */
final readonly class HttpProductionErrorRouteRenderer implements HttpExceptionRenderer
{
    /**
     * Creates a new HttpProductionErrorRouteRenderer instance.
     */
    public function __construct(
        private Router $router,
        private ControllerDispatcher $dispatcher,
        private HttpProductionExceptionRenderer $fallbackRenderer,
        private string $route = '/error/{status}',
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): HttpResponse
    {
        $statusCode = $throwable instanceof HttpException ? $throwable->statusCode() : 500;
        $headers = $throwable instanceof HttpException ? $throwable->headers() : [];
        $request = $this->request($statusCode);

        try {
            $response = $this->dispatcher->dispatch($request, $this->router->routes()->match('GET', $request->path()))
                ->withStatus($statusCode);
        } catch (Throwable) {
            return $this->fallbackRenderer->render($throwable);
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function request(int $statusCode): HttpRequest
    {
        return HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => str_replace('{status}', (string) $statusCode, $this->route),
        ]);
    }
}
