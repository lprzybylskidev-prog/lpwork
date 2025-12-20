<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Coordinates session lifecycle per request.
 */
class SessionManager
{
    /**
     * @var SessionConfiguration
     */
    private SessionConfiguration $configuration;

    /**
     * @var SessionStoreInterface
     */
    private SessionStoreInterface $store;

    /**
     * @var SessionIdGeneratorInterface
     */
    private SessionIdGeneratorInterface $idGenerator;

    /**
     * @var SessionContext|null
     */
    private ?SessionContext $context = null;

    /**
     * @var SessionCookieParameters|null
     */
    private ?SessionCookieParameters $cookieParameters = null;

    /**
     * @var string|null
     */
    private ?string $initialId = null;

    /**
     * @var SessionInterface|null
     */
    private ?SessionInterface $currentSession = null;

    /**
     * @param SessionConfiguration          $configuration
     * @param SessionStoreInterface         $store
     * @param SessionIdGeneratorInterface   $idGenerator
     */
    public function __construct(
        SessionConfiguration $configuration,
        SessionStoreInterface $store,
        SessionIdGeneratorInterface $idGenerator,
    ) {
        $this->configuration = $configuration;
        $this->store = $store;
        $this->idGenerator = $idGenerator;
    }

    /**
     * Starts session from request and returns session facade.
     *
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    public function start(ServerRequestInterface $request): SessionInterface
    {
        $cookieParameters = $this->buildCookieParameters($request);
        $sessionId = $this->resolveSessionId($request, $cookieParameters);
        $state = $this->store->start(
            $sessionId,
            $cookieParameters,
            $this->configuration->lifetime(),
        );

        $this->context = new SessionContext($state);
        $this->cookieParameters = $cookieParameters;
        $this->initialId = $state->id();
        $this->currentSession = new Session(
            $state,
            $this->context,
            $this->idGenerator,
        );

        return $this->currentSession;
    }

    /**
     * Persists session and appends cookie header if needed.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function persist(ResponseInterface $response): ResponseInterface
    {
        if ($this->context === null || $this->cookieParameters === null) {
            return $response;
        }

        $state = $this->context->state();
        $cookieParameters = $this->cookieParameters;

        if ($this->initialId !== null && $this->initialId !== $state->id()) {
            $this->store->destroy($this->initialId);
            $this->initialId = $state->id();
        }

        $this->store->persist(
            $state,
            $cookieParameters,
            $this->configuration->lifetime(),
        );

        if ($this->store->usesNativeCookie()) {
            return $response;
        }

        return $response->withAddedHeader(
            "Set-Cookie",
            $this->buildCookieHeader($cookieParameters, $state->id()),
        );
    }

    /**
     * Returns current session instance.
     *
     * @return SessionInterface
     */
    public function current(): SessionInterface
    {
        if ($this->currentSession === null) {
            throw new \LogicException("Session has not been started.");
        }

        return $this->currentSession;
    }

    /**
     * Builds cookie parameters from configuration and request scheme.
     *
     * @param ServerRequestInterface $request
     *
     * @return SessionCookieParameters
     */
    private function buildCookieParameters(
        ServerRequestInterface $request,
    ): SessionCookieParameters {
        $cookie = $this->configuration->cookie();
        $scheme = \strtolower($request->getUri()->getScheme());
        $forceSecure = (bool) ($cookie["secure"] ?? false);
        $isHttps = $scheme === "https";
        $secure = $forceSecure || $isHttps;
        $sameSite = \strtolower((string) ($cookie["same_site"] ?? "lax"));
        $normalizedSameSite = \in_array(
            $sameSite,
            ["lax", "strict", "none"],
            true,
        )
            ? $sameSite
            : "lax";

        $cookieName = (string) ($cookie["name"] ?? "LPWORKSESSID");

        if ($this->configuration->driver() === "php") {
            $phpConfig = $this->configuration->driverConfig("php");
            $cookieName = (string) ($phpConfig["name"] ?? $cookieName);
        }

        return new SessionCookieParameters(
            $cookieName,
            $this->configuration->lifetime(),
            (string) ($cookie["path"] ?? "/"),
            (string) ($cookie["domain"] ?? ""),
            $secure,
            (bool) ($cookie["http_only"] ?? true),
            $normalizedSameSite,
        );
    }

    /**
     * Resolves session identifier from cookie.
     *
     * @param ServerRequestInterface   $request
     * @param SessionCookieParameters  $cookieParameters
     *
     * @return string|null
     */
    private function resolveSessionId(
        ServerRequestInterface $request,
        SessionCookieParameters $cookieParameters,
    ): ?string {
        $cookies = $request->getCookieParams();

        $id = $cookies[$cookieParameters->name()] ?? null;

        if ($id === null || $id === "") {
            return null;
        }

        return (string) $id;
    }

    /**
     * @param SessionCookieParameters $cookieParameters
     * @param string                  $id
     *
     * @return string
     */
    private function buildCookieHeader(
        SessionCookieParameters $cookieParameters,
        string $id,
    ): string {
        $parts = [];
        $parts[] = \sprintf("%s=%s", $cookieParameters->name(), $id);
        $parts[] = \sprintf("Max-Age=%d", $cookieParameters->lifetime());
        $parts[] = \sprintf("Path=%s", $cookieParameters->path());

        if ($cookieParameters->domain() !== "") {
            $parts[] = \sprintf("Domain=%s", $cookieParameters->domain());
        }

        if ($cookieParameters->secure()) {
            $parts[] = "Secure";
        }

        if ($cookieParameters->httpOnly()) {
            $parts[] = "HttpOnly";
        }

        $parts[] = \sprintf(
            "SameSite=%s",
            \ucfirst(\strtolower($cookieParameters->sameSite())),
        );

        return \implode("; ", $parts);
    }
}
