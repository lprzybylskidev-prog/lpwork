<?php

declare(strict_types=1);

namespace LPWork\Console\Enums;

/**
 * Enumerates the supported console style values.
 */
enum ConsoleStyle: int
{
    case Bold = 1;
    case Dim = 2;
    case Underline = 4;
    case Reversed = 7;
}
