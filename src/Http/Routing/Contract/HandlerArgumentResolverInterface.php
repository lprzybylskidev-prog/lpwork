<?php
declare(strict_types=1);

namespace LPwork\Http\Routing\Contract;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves arguments for a route handler based on request and parameters.
 */
interface HandlerArgumentResolverInterface
{
    /**
     * @param callable               $handler
     * @param ServerRequestInterface $request
     * @param array<string, string>  $routeParams
     *
     * @return array<int, mixed>
     */
    public function resolveArguments(
        callable $handler,
        ServerRequestInterface $request,
        array $routeParams,
    ): array;
}
