<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

use LPWork\DebugDump\Exceptions\DebugDumperNotConfiguredException;
use LPWork\Shared\Concerns\PreventsSerialization;

/**
 * Represents the debug framework component.
 */
final class Debug
{
    use PreventsSerialization;

    private static ?DebugDumper $dumper = null;

    private function __construct() {}

    private function __clone() {}

    /**
     * Registers or stores set dumper.
     */
    public static function setDumper(DebugDumper $dumper): void
    {
        self::$dumper = $dumper;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public static function reset(): void
    {
        self::$dumper = null;
    }

    /**
     * Returns label.
     */
    public static function label(string $label): DebugDumpLabel
    {
        if (self::$dumper === null) {
            throw new DebugDumperNotConfiguredException();
        }

        return new DebugDumpLabel(self::$dumper, $label);
    }

    /**
     * Performs the d operation.
     */
    public static function d(mixed ...$values): mixed
    {
        if (self::$dumper === null) {
            throw new DebugDumperNotConfiguredException();
        }

        return self::$dumper->dump(...$values);
    }

    /**
     * Performs the dd operation.
     */
    public static function dd(mixed ...$values): never
    {
        if (self::$dumper === null) {
            throw new DebugDumperNotConfiguredException();
        }

        self::$dumper->terminate(...$values);
    }
}
