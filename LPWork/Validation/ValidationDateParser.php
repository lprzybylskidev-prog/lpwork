<?php

declare(strict_types=1);

namespace LPWork\Validation;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Represents the validation date parser framework component.
 */
final readonly class ValidationDateParser
{
    /**
     * Builds or returns parse.
     */
    public function parse(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new DateTimeImmutable($value->format(DateTimeInterface::ATOM));
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Builds or returns parse format.
     */
    public function parseFormat(mixed $value, string $format): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat($format, $value);

        if (!$date instanceof DateTimeImmutable) {
            return null;
        }

        return $date->format($format) === $value ? $date : null;
    }
}
