<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use function fclose;
use function fsockopen;
use function is_resource;
use function is_string;

use LPWork\Frontend\Contracts\ApplicationAssetDevServerProbe;

use function parse_url;
use function restore_error_handler;
use function set_error_handler;

/**
 * Represents the socket application asset dev server probe framework component.
 */
final readonly class SocketApplicationAssetDevServerProbe implements ApplicationAssetDevServerProbe
{
    /**
     * Creates a new SocketApplicationAssetDevServerProbe instance.
     */
    public function __construct(
        private float $timeoutSeconds = 0.2,
    ) {}

    /**
     * Performs the reachable operation.
     */
    public function reachable(string $url): bool
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;

        if (!is_string($host) || $host === '') {
            return false;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $errno = 0;
        $errstr = '';

        set_error_handler(static fn(): bool => true);

        try {
            $socket = fsockopen($host, $port, $errno, $errstr, $this->timeoutSeconds);
        } finally {
            restore_error_handler();
        }

        if (!is_resource($socket)) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
