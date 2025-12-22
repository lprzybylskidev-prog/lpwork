<?php
declare(strict_types=1);

namespace LPwork\Queue\Messenger\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Generic container exception for queue service locators.
 */
final class QueueContainerException extends \RuntimeException implements
    ContainerExceptionInterface {}
