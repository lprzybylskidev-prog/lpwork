<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use JsonException;
use LPWork\Http\Cookie;
use LPWork\Responses\HttpResponse;
use LPWork\Session\Session;
use PHPUnit\Framework\Assert;
use Tests\support\exceptions\TestSupportException;

final readonly class TestResponse
{
    public function __construct(
        private HttpResponse $response,
        private ?Session $session = null,
    ) {}

    public function baseResponse(): HttpResponse
    {
        return $this->response;
    }

    public function withSession(Session $session): self
    {
        return new self($this->response, $session);
    }

    public function statusCode(): int
    {
        return $this->response->statusCode();
    }

    public function body(): string
    {
        return $this->response->body();
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->response->header($name, $default);
    }

    /**
     * @return list<Cookie>
     */
    public function cookies(): array
    {
        return $this->response->cookies();
    }

    public function assertStatus(int $status): self
    {
        Assert::assertSame($status, $this->response->statusCode(), 'Unexpected HTTP response status.');

        return $this;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }

    public function assertNoContent(): self
    {
        return $this->assertStatus(204)->assertEmptyBody();
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    public function assertRedirect(?string $location = null): self
    {
        Assert::assertContains(
            $this->response->statusCode(),
            [301, 302, 303, 307, 308],
            'Response is not a redirect response.',
        );

        if ($location !== null) {
            $this->assertHeader('Location', $location);
        }

        return $this;
    }

    public function assertHeader(string $name, string $value): self
    {
        Assert::assertSame($value, $this->response->header($name), sprintf('Unexpected [%s] header value.', $name));

        return $this;
    }

    public function assertHeaderMissing(string $name): self
    {
        Assert::assertNull($this->response->header($name), sprintf('Header [%s] exists unexpectedly.', $name));

        return $this;
    }

    public function assertCookie(string $name, ?string $value = null): self
    {
        $cookie = $this->cookie($name);

        Assert::assertNotNull($cookie, sprintf('Cookie [%s] does not exist.', $name));

        if ($value !== null) {
            Assert::assertStringContainsString(
                rawurlencode($name) . '=' . rawurlencode($value),
                $cookie->toHeader(),
                sprintf('Cookie [%s] does not have the expected value.', $name),
            );
        }

        return $this;
    }

    public function assertCookieMissing(string $name): self
    {
        Assert::assertNull($this->cookie($name), sprintf('Cookie [%s] exists unexpectedly.', $name));

        return $this;
    }

    public function assertCookieExpired(string $name): self
    {
        $cookie = $this->cookie($name);

        Assert::assertNotNull($cookie, sprintf('Cookie [%s] does not exist.', $name));
        Assert::assertStringContainsString('Max-Age=-', $cookie->toHeader(), sprintf('Cookie [%s] is not expired.', $name));

        return $this;
    }

    public function assertSee(string $text): self
    {
        Assert::assertStringContainsString($text, $this->response->body());

        return $this;
    }

    public function assertDontSee(string $text): self
    {
        Assert::assertStringNotContainsString($text, $this->response->body());

        return $this;
    }

    public function assertBody(string $body): self
    {
        Assert::assertSame($body, $this->response->body(), 'Unexpected HTTP response body.');

        return $this;
    }

    public function assertEmptyBody(): self
    {
        return $this->assertBody('');
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     */
    public function assertExactJson(array $data): self
    {
        Assert::assertSame($data, $this->decodedJson(), 'Unexpected JSON response body.');

        return $this;
    }

    public function assertJsonPath(string $path, mixed $value): self
    {
        Assert::assertSame($value, $this->jsonPath($path), sprintf('Unexpected JSON value at [%s].', $path));

        return $this;
    }

    /**
     * @param array<string, mixed>|list<mixed> $fragment
     */
    public function assertJsonFragment(array $fragment): self
    {
        Assert::assertTrue(
            $this->containsJsonFragment($this->decodedJson(), $fragment),
            'JSON response body does not contain the expected fragment.',
        );

        return $this;
    }

    /**
     * @param array<string, mixed>|list<mixed> $fragment
     */
    public function assertJsonMissingFragment(array $fragment): self
    {
        Assert::assertFalse(
            $this->containsJsonFragment($this->decodedJson(), $fragment),
            'JSON response body contains the unexpected fragment.',
        );

        return $this;
    }

    public function assertValidJson(): self
    {
        $this->decodedJson();

        return $this;
    }

    public function assertSessionHas(string $key, mixed ...$value): self
    {
        $session = $this->session();

        Assert::assertTrue($session->has($key), sprintf('Session key [%s] does not exist.', $key));

        if ($value !== []) {
            Assert::assertSame($value[0], $session->get($key), sprintf('Unexpected session value for [%s].', $key));
        }

        return $this;
    }

    public function assertSessionMissing(string $key): self
    {
        Assert::assertFalse($this->session()->has($key), sprintf('Session key [%s] exists unexpectedly.', $key));

        return $this;
    }

    public function assertOldInput(string $key, mixed $value): self
    {
        Assert::assertSame($value, $this->session()->old($key), sprintf('Unexpected old input value for [%s].', $key));

        return $this;
    }

    public function assertSessionError(string $key, mixed $value): self
    {
        Assert::assertSame($value, $this->session()->error($key), sprintf('Unexpected session error for [%s].', $key));

        return $this;
    }

    private function cookie(string $name): ?Cookie
    {
        foreach ($this->response->cookies() as $cookie) {
            if ($cookie->name() === $name) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    private function decodedJson(): array
    {
        try {
            $decoded = json_decode($this->response->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Assert::fail('Response body is not valid JSON: ' . $exception->getMessage());
        }

        Assert::assertIsArray($decoded, 'JSON response body must decode to an array.');

        return $decoded;
    }

    private function jsonPath(string $path): mixed
    {
        $value = $this->decodedJson();

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                Assert::fail(sprintf('JSON path [%s] does not exist.', $path));
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param array<mixed> $json
     * @param array<mixed> $fragment
     */
    private function containsJsonFragment(array $json, array $fragment): bool
    {
        if ($this->jsonContainsSubset($json, $fragment)) {
            return true;
        }

        foreach ($json as $value) {
            if (is_array($value) && $this->containsJsonFragment($value, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $json
     * @param array<mixed> $fragment
     */
    private function jsonContainsSubset(array $json, array $fragment): bool
    {
        foreach ($fragment as $key => $value) {
            if (!array_key_exists($key, $json)) {
                return false;
            }

            if (is_array($value)) {
                if (!is_array($json[$key]) || !$this->jsonContainsSubset($json[$key], $value)) {
                    return false;
                }

                continue;
            }

            if ($json[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    private function session(): Session
    {
        return $this->session ?? throw TestSupportException::testResponseHasNoSession();
    }
}
