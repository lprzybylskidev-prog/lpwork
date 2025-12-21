<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Contract;

/**
 * Logs throwable instances and returns generated error IDs.
 */
interface ErrorLoggerInterface
{
    /**
     * Logs the throwable with optional context and returns error identifier.
     *
     * @param \Throwable           $throwable
     * @param array<string, mixed> $context
     *
     * @return string
     */
    public function log(\Throwable $throwable, array $context = []): string;
}
