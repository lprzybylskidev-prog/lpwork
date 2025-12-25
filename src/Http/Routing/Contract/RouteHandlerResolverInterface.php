<?php
declare(strict_types=1);

namespace LPwork\Http\Routing\Contract;

/**
 * Resolves route handler definitions into callables.
 */
interface RouteHandlerResolverInterface
{
    /**
     * @param mixed $handlerDefinition
     *
     * @return callable|null
     */
    public function resolve(mixed $handlerDefinition): callable|null;
}
