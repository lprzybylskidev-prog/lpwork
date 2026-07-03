<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Requests\RequestDataAccessor;
use LPWork\Session\Session;

/**
 * Represents the form errors framework component.
 */
final readonly class FormErrors
{
    private const string KEY = '_errors';

    /**
     * @param array<string, mixed> $errors
     */
    public static function flash(Session $session, array $errors): void
    {
        $session->flash(self::KEY, $errors);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(Session $session): array
    {
        $errors = $session->get(self::KEY, []);

        if (!is_array($errors)) {
            return [];
        }

        $values = [];

        foreach ($errors as $key => $value) {
            if (is_string($key)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * Returns the requested value from this component.
     */
    public static function get(Session $session, string $key, mixed $default = null): mixed
    {
        return new RequestDataAccessor(self::all($session))->value($key, $default);
    }
}
