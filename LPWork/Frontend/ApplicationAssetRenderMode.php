<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Enumerates the supported application asset render mode values.
 */
enum ApplicationAssetRenderMode
{
    case DevServer;
    case Manifest;
}
