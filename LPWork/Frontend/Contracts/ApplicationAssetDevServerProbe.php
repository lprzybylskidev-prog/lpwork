<?php

declare(strict_types=1);

namespace LPWork\Frontend\Contracts;

/**
 * Defines the contract for application asset dev server probe.
 */
interface ApplicationAssetDevServerProbe
{
    /**
     * Performs the reachable operation.
     */
    public function reachable(string $url): bool;
}
