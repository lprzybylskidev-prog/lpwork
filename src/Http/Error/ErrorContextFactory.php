<?php
declare(strict_types=1);

namespace LPwork\Http\Error;

use Carbon\CarbonImmutable;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Http\Middleware\SessionMiddleware;
use LPwork\Http\Request\RequestContext;
use LPwork\Http\Request\RequestContextStore;
use LPwork\Http\Session\Contract\SessionInterface;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Builds ErrorContext instances from HTTP requests.
 */
class ErrorContextFactory
{
    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $config;

    /**
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @param ConfigRepositoryInterface $config
     * @param ClockInterface            $clock
     */
    public function __construct(ConfigRepositoryInterface $config, ClockInterface $clock)
    {
        $this->config = $config;
        $this->clock = $clock;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $errorId
     * @param int                    $status
     * @param \Throwable|null        $throwable
     *
     * @return ErrorContext
     */
    public function fromRequest(
        ServerRequestInterface $request,
        string $errorId,
        int $status,
        ?\Throwable $throwable = null,
    ): ErrorContext {
        $session = $this->extractSession($request);
        $app = $this->config->get('app', []);

        return new ErrorContext(
            $errorId,
            $status,
            $throwable?->getMessage(),
            $throwable !== null ? \get_class($throwable) : null,
            $throwable?->getCode() !== null ? (int) $throwable->getCode() : null,
            $throwable?->getFile(),
            $throwable?->getLine(),
            $throwable?->getTraceAsString(),
            $this->requestData($request),
            $this->sessionData($session),
            $_ENV,
            $app,
            CarbonImmutable::instance($this->clock->now()),
        );
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array<string, mixed>
     */
    private function requestData(ServerRequestInterface $request): array
    {
        $data = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'query_string' => $request->getUri()->getQuery(),
            'query_params' => $request->getQueryParams(),
            'parsed_body' => $request->getParsedBody(),
            'files' => $request->getUploadedFiles(),
            'headers' => $request->getHeaders(),
            'cookies' => $request->getCookieParams(),
        ];

        /** @var RequestContext|null $context */
        $context = $request->getAttribute(RequestContext::ATTRIBUTE);

        if ($context instanceof RequestContext) {
            $data['route'] = [
                'name' => $context->routeName(),
                'parameters' => $context->parameters(),
                'middleware' => $context->middleware(),
            ];
        } else {
            $contextFromStore = RequestContextStore::get();

            if ($contextFromStore instanceof RequestContext) {
                $data['route'] = [
                    'name' => $contextFromStore->routeName(),
                    'parameters' => $contextFromStore->parameters(),
                    'middleware' => $contextFromStore->middleware(),
                ];
            }

            $legacyName = $request->getAttribute('route_name');
            $legacyParams = $request->getAttribute('route_params', []);
            $legacyMiddleware = $request->getAttribute('route_middleware', []);

            if ($legacyName !== null || $legacyParams !== [] || $legacyMiddleware !== []) {
                $data['route'] = [
                    'name' => \is_string($legacyName) ? $legacyName : null,
                    'parameters' => \is_array($legacyParams) ? $legacyParams : [],
                    'middleware' => \is_array($legacyMiddleware) ? $legacyMiddleware : [],
                ];
            }
        }

        return $data;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface|null
     */
    private function extractSession(ServerRequestInterface $request): ?SessionInterface
    {
        $session = $request->getAttribute(SessionMiddleware::ATTRIBUTE);

        if ($session instanceof SessionInterface) {
            return $session;
        }

        return null;
    }

    /**
     * @param SessionInterface|null $session
     *
     * @return array<string, mixed>
     */
    private function sessionData(?SessionInterface $session): array
    {
        if ($session === null) {
            return ['info' => 'no session'];
        }

        return [
            'id' => $session->id(),
            'data' => $session->all(),
        ];
    }
}
