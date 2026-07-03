<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Requests\RequestDataAccessor;
use LPWork\Session\Session;

/**
 * Represents the old input framework component.
 */
final readonly class OldInput
{
    private const string KEY = '_old_input';

    /**
     * @param array<string, mixed> $input
     */
    /**
     * @param array<string, mixed> $input
     * @param list<string> $except
     */
    public static function flash(Session $session, array $input, array $except = []): void
    {
        if ($except !== []) {
            $input = new RequestDataAccessor($input)->except($except);
        }

        $session->flash(self::KEY, $input);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(Session $session): array
    {
        $input = $session->get(self::KEY, []);

        if (!is_array($input)) {
            return [];
        }

        $values = [];

        foreach ($input as $key => $value) {
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
