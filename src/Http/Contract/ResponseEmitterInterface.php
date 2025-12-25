<?php
declare(strict_types=1);

namespace LPwork\Http\Contract;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Emits HTTP responses to the client.
 */
interface ResponseEmitterInterface
{
    /**
     * @param ResponseInterface       $response
     * @param ServerRequestInterface  $request
     *
     * @return void
     */
    public function emit(ResponseInterface $response, ServerRequestInterface $request): void;
}
