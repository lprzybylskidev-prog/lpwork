<?php

declare(strict_types=1);

namespace LPWork\Responses\Enums;

/**
 * Enumerates the supported response format values.
 */
enum ResponseFormat
{
    case Html;
    case Json;
    case Cli;
}
