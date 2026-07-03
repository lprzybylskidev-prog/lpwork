<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

/**
 * Reports application asset dev server unavailable exception failures.
 */
final class ApplicationAssetDevServerUnavailableException extends ApplicationAssetException
{
    /**
     * Creates a new ApplicationAssetDevServerUnavailableException instance.
     */
    public function __construct(string $url)
    {
        parent::__construct("Vite dev server is not reachable at [{$url}]. Start it with php lpwork frontend:dev or build assets with php lpwork frontend:build.");
    }
}
