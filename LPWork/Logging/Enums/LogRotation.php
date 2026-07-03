<?php

declare(strict_types=1);

namespace LPWork\Logging\Enums;

/**
 * Enumerates the supported log rotation values.
 */
enum LogRotation: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
