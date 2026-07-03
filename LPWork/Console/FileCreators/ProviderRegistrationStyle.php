<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Enumerates the supported provider registration style values.
 */
enum ProviderRegistrationStyle
{
    case List;
    case Grouped;
}
