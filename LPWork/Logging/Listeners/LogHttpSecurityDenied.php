<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Logging\Contracts\Logger;
use LPWork\Security\Events\HttpSecurityDenied;

/**
 * Represents the log http security denied framework component.
 */
final readonly class LogHttpSecurityDenied
{
    /**
     * Creates a new LogHttpSecurityDenied instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpSecurityDenied $event): void
    {
        $this->logger->warning($event->message, $event->context);
    }
}
