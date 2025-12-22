<?php
declare(strict_types=1);

namespace LPwork\Queue\Messenger;

use LPwork\Queue\Messenger\Exception\QueueContainerException;
use LPwork\Queue\Messenger\Exception\QueueNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Lightweight service locator implementing PSR-11 for Messenger listeners.
 */
final class QueueServiceLocator implements ContainerInterface
{
    /**
     * @var array<string, callable(): mixed|mixed>
     */
    private array $services;

    /**
     * @param array<string, callable(): mixed|mixed> $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new QueueNotFoundException(
                \sprintf('Service "%s" is not defined.', $id),
            );
        }

        $service = $this->services[$id];

        try {
            return \is_callable($service) ? $service() : $service;
        } catch (\Throwable $exception) {
            throw new QueueContainerException(
                \sprintf('Failed to resolve service "%s".', $id),
                0,
                $exception,
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->services);
    }
}
