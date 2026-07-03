<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Kernels\Http\HttpKernel;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Requests\HttpRequest;
use LPWork\Routing\Router;
use LPWork\Security\SecurityConfig;
use Tests\support\exceptions\TestSupportException;
use Tests\support\testing\Session\TestSessionStore;

final class HttpTestClient
{
    private readonly CapturingHttpEmitter $emitter;

    private readonly CookieJar $cookies;

    private ?TestSessionStore $session = null;

    public function __construct(
        private readonly Application $app,
        private ?Router $router = null,
        ?CapturingHttpEmitter $emitter = null,
        ?CookieJar $cookies = null,
    ) {
        $this->emitter = $emitter ?? new CapturingHttpEmitter();
        $this->cookies = $cookies ?? new CookieJar();
    }

    public static function forApplication(Application $app, ?Router $router = null): self
    {
        return new self($app, $router);
    }

    public function send(HttpRequest $request): TestResponse
    {
        new HttpKernel($this->app, $this->emitter, $this->router)->handle($request);

        $response = $this->response();
        $this->cookies->capture($response->baseResponse());

        return $response;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $input
     * @param array<string, string> $headers
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function request(
        string $method,
        string $uri,
        array $query = [],
        array $input = [],
        array $headers = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        string $body = '',
    ): TestResponse {
        $secure = ($server['HTTPS'] ?? null) === 'on'
            || ($server['REQUEST_SCHEME'] ?? null) === 'https'
            || ($server['SERVER_PORT'] ?? null) === '443';

        return $this->send(
            HttpRequestBuilder::request($method, $uri)
                ->withQuery($query)
                ->withInput($input)
                ->withHeaders($headers)
                ->withCookies([
                    ...$this->cookies->requestCookies($uri, $secure),
                    ...$cookies,
                ])
                ->withFiles($files)
                ->withServer($server)
                ->withBody($body)
                ->build(),
        );
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     * @param array<string, string> $headers
     */
    public function json(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $secure = false;
        $builder = HttpRequestBuilder::request($method, $uri)
            ->withCookies($this->cookies->requestCookies($uri, $secure))
            ->withJsonBody($data);

        if ($headers !== []) {
            $builder->withHeaders($headers);
        }

        return $this->send($builder->build());
    }

    public function withCookie(string $name, string $value, string $path = '/', string $domain = '', bool $secure = false): self
    {
        $this->cookies->put($name, $value, $path, $domain, $secure);

        return $this;
    }

    public function withoutCookie(string $name, string $path = '/', string $domain = ''): self
    {
        $this->cookies->forget($name, $path, $domain);

        return $this;
    }

    public function clearCookies(): self
    {
        $this->cookies->clear();

        return $this;
    }

    public function cookieJar(): CookieJar
    {
        return $this->cookies;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function withSession(array $data = []): self
    {
        return $this->usingSession(TestSessionStore::seeded($data));
    }

    public function usingSession(TestSessionStore $session): self
    {
        $this->session = $session;
        $this->app->container()->instance(SessionMiddleware::class, new SessionMiddleware($session->driver()));

        if (!$this->webSecurityMiddlewareAttachesSession()) {
            $this->registerSessionMiddleware();
        }

        return $this;
    }

    public function session(): TestSessionStore
    {
        if ($this->session === null) {
            $this->usingSession(TestSessionStore::seeded([]));
        }

        $session = $this->session;

        if ($session === null) {
            throw TestSupportException::testResponseHasNoSession();
        }

        return $session;
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     * @param array<string, string> $headers
     */
    public function postJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     * @param array<string, string> $headers
     */
    public function putJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     * @param array<string, string> $headers
     */
    public function patchJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('PATCH', $uri, $data, $headers);
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     * @param array<string, string> $headers
     */
    public function deleteJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    public function get(string $uri, array $query = [], array $headers = []): TestResponse
    {
        return $this->request('GET', $uri, query: $query, headers: $headers);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $headers
     */
    public function post(string $uri, array $input = [], array $headers = []): TestResponse
    {
        return $this->request('POST', $uri, input: $input, headers: $headers);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $headers
     */
    public function put(string $uri, array $input = [], array $headers = []): TestResponse
    {
        return $this->request('PUT', $uri, input: $input, headers: $headers);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $headers
     */
    public function patch(string $uri, array $input = [], array $headers = []): TestResponse
    {
        return $this->request('PATCH', $uri, input: $input, headers: $headers);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $headers
     */
    public function delete(string $uri, array $input = [], array $headers = []): TestResponse
    {
        return $this->request('DELETE', $uri, input: $input, headers: $headers);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    public function options(string $uri, array $query = [], array $headers = []): TestResponse
    {
        return $this->request('OPTIONS', $uri, query: $query, headers: $headers);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    public function head(string $uri, array $query = [], array $headers = []): TestResponse
    {
        return $this->request('HEAD', $uri, query: $query, headers: $headers);
    }

    public function response(): TestResponse
    {
        if ($this->emitter->response === null) {
            throw TestSupportException::httpClientDidNotCaptureResponse();
        }

        return new TestResponse($this->emitter->response, $this->session?->session());
    }

    public function emittedResponses(): int
    {
        return $this->emitter->calls;
    }

    private function router(): Router
    {
        if ($this->router instanceof Router) {
            return $this->router;
        }

        $router = $this->app->container()->make(Router::class);

        if ($router instanceof Router) {
            $this->router = $router;

            return $router;
        }

        $this->router = new Router();
        $this->app->container()->instance(Router::class, $this->router);

        return $this->router;
    }

    private function registerSessionMiddleware(): void
    {
        $router = $this->router();

        if (in_array(SessionMiddleware::class, $router->globalMiddlewareList(), true)) {
            return;
        }

        $router->globalMiddleware(SessionMiddleware::class);
    }

    private function webSecurityMiddlewareAttachesSession(): bool
    {
        try {
            $security = $this->app->container()->make(SecurityConfig::class);
        } catch (CannotResolveDependencyException) {
            return false;
        }

        return $security instanceof SecurityConfig && $security->csrf()->enabled();
    }
}
