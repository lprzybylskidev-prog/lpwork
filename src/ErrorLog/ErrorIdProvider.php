<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;

/**
 * In-memory holder for the current error identifier.
 */
class ErrorIdProvider implements ErrorIdProviderInterface
{
    /**
     * @var string|null
     */
    private ?string $currentErrorId = null;

    /**
     * @inheritDoc
     */
    public function getCurrentErrorId(): ?string
    {
        return $this->currentErrorId;
    }

    /**
     * @inheritDoc
     */
    public function setCurrentErrorId(string $errorId): void
    {
        $this->currentErrorId = $errorId;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->currentErrorId = null;
    }
}
