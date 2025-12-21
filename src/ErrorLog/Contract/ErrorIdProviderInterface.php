<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Contract;

/**
 * Exposes the current error identifier for application-level access.
 */
interface ErrorIdProviderInterface
{
    /**
     * Returns the last error identifier recorded in the current runtime scope.
     *
     * @return string|null
     */
    public function getCurrentErrorId(): ?string;

    /**
     * Stores the given error identifier for later retrieval.
     *
     * @param string $errorId
     *
     * @return void
     */
    public function setCurrentErrorId(string $errorId): void;

    /**
     * Clears the stored error identifier.
     *
     * @return void
     */
    public function clear(): void;
}
