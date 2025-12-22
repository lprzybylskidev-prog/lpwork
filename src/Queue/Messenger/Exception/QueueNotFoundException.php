<?php
declare(strict_types=1);

namespace LPwork\Queue\Messenger\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when a requested queue service is missing.
 */
final class QueueNotFoundException extends \RuntimeException implements
    NotFoundExceptionInterface {}
