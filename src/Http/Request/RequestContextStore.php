<?php
declare(strict_types=1);

namespace LPwork\Http\Request;

/**
 * Simple per-request context store to expose routing context when attributes are unavailable.
 */
final class RequestContextStore
{
    /**
     * @var RequestContext|null
     */
    private static ?RequestContext $current = null;

    /**
     * @param RequestContext $context
     *
     * @return void
     */
    public static function set(RequestContext $context): void
    {
        self::$current = $context;
    }

    /**
     * @return RequestContext|null
     */
    public static function get(): ?RequestContext
    {
        return self::$current;
    }

    /**
     * @return void
     */
    public static function clear(): void
    {
        self::$current = null;
    }
}
