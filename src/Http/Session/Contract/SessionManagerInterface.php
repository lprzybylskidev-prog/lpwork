<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Contract;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contract for session lifecycle coordination.
 */
interface SessionManagerInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    public function start(ServerRequestInterface $request): SessionInterface;

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function persist(ResponseInterface $response): ResponseInterface;

    /**
     * @return SessionInterface
     */
    public function current(): SessionInterface;
}
