<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Http\Session\Contract\SessionManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Starts and persists session for each HTTP request.
 */
class SessionMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    public const ATTRIBUTE = 'session';

    /**
     * @var SessionManagerInterface
     */
    private SessionManagerInterface $sessionManager;

    /**
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $session = $this->sessionManager->start($request);
        $requestWithSession = $request->withAttribute(self::ATTRIBUTE, $session);

        $response = $handler->handle($requestWithSession);

        return $this->sessionManager->persist($response);
    }
}
