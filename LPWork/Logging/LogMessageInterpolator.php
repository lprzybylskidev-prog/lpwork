<?php

declare(strict_types=1);

namespace LPWork\Logging;

use Stringable;

/**
 * Represents the log message interpolator framework component.
 */
final readonly class LogMessageInterpolator
{
    /**
     * @param array<string, mixed> $context
     */
    public function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if ($value === null) {
                $replace['{' . $key . '}'] = '';

                continue;
            }

            if (is_scalar($value) || $value instanceof Stringable) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }
}
