<?php

declare(strict_types=1);

namespace LPWork\Security\Listeners;

use LPWork\Security\Events\HttpSecurityDenied;
use LPWork\Security\SecurityDebugCollector;

/**
 * Represents the record http security denied framework component.
 */
final readonly class RecordHttpSecurityDenied
{
    /**
     * Creates a new RecordHttpSecurityDenied instance.
     */
    public function __construct(
        private SecurityDebugCollector $collector,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpSecurityDenied $event): void
    {
        $this->collector->denied($event->reason, $event->message, $event->context);
    }
}
