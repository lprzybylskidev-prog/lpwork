<?php
declare(strict_types=1);

namespace LPwork\Http\Routing\Contract;

use LPwork\Http\Routing\RouteCollection;

/**
 * Contract for loading routes from definitions.
 */
interface RouteLoaderInterface
{
    /**
     * @return RouteCollection
     */
    public function load(): RouteCollection;
}
