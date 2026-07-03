<?php

declare(strict_types=1);

namespace LPWork\Config\Enums;

/**
 * Enumerates the supported environment requirement type values.
 */
enum EnvironmentRequirementType: string
{
    case String = 'string';
    case Integer = 'int';
    case Float = 'float';
    case Boolean = 'bool';
}
