<?php

declare(strict_types=1);

namespace LPWork\Broadcasting;

use LPWork\Broadcasting\Contracts\BroadcastableEvent;
use LPWork\Broadcasting\Contracts\Broadcaster;
use LPWork\Broadcasting\Events\BroadcastFailed;
use LPWork\Broadcasting\Events\BroadcastSending;
use LPWork\Broadcasting\Events\BroadcastSent;
use LPWork\Broadcasting\Exceptions\InvalidBroadcasterException;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastingConfigException;
use LPWork\Broadcasting\Exceptions\MissingBroadcastingConfigException;
use LPWork\Config\NamedDriverConfig;
use LPWork\Config\NamedDriverConfigFactory;
use LPWork\Events\EventDispatcher;
use Throwable;

/**
 * Resolves broadcasters and broadcasts messages or broadcastable events.
 */
final class BroadcastManager
{
    /**
     * @var array<string, Broadcaster>
     */
    private array $broadcasters = [];

    private NamedDriverConfig $connectionConfig;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly BroadcastDriverFactory $driverFactory,
        private readonly ?EventDispatcher $events = null,
    ) {
        $this->connectionConfig = $this->connectionConfig($config);
    }

    /**
     * Returns the configured default broadcaster.
     */
    public function default(): Broadcaster
    {
        return $this->broadcaster($this->defaultBroadcasterName());
    }

    /**
     * Returns the broadcaster name used when broadcast calls omit one.
     */
    public function defaultBroadcasterName(): string
    {
        return $this->connectionConfig->defaultName();
    }

    /**
     * Returns a named broadcaster, creating and caching it on first use.
     */
    public function broadcaster(string $name): Broadcaster
    {
        if (array_key_exists($name, $this->broadcasters)) {
            return $this->broadcasters[$name];
        }

        $config = $this->connectionConfig->entry($name, static fn(string $name): InvalidBroadcasterException => new InvalidBroadcasterException($name));

        $this->broadcasters[$name] = $this->driverFactory->create($name, $config, $this->connectionConfig->entryKey($name));

        return $this->broadcasters[$name];
    }

    /**
     * Broadcasts a message through the selected broadcaster and emits lifecycle events.
     */
    public function broadcast(BroadcastMessage|BroadcastableEvent $message, ?string $broadcaster = null): BroadcastResult
    {
        $message = $message instanceof BroadcastableEvent
            ? new BroadcastMessage($message->broadcastChannels(), $message->broadcastName(), $message->broadcastPayload())
            : $message;

        $this->events?->dispatch(new BroadcastSending($message->name(), $message->channels()));

        try {
            $result = ($broadcaster === null ? $this->default() : $this->broadcaster($broadcaster))->broadcast($message);
        } catch (Throwable $throwable) {
            $this->events?->dispatch(new BroadcastFailed($message->name(), $message->channels(), $throwable::class));

            throw $throwable;
        }

        $this->events?->dispatch(new BroadcastSent($result->event, $result->channels, $result->driver));

        return $result;
    }

    /**
     * Returns all configured broadcaster names.
     *
     * @return list<string>
     */
    public function broadcasterNames(): array
    {
        return $this->connectionConfig->names();
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function connectionConfig(array $config): NamedDriverConfig
    {
        return new NamedDriverConfigFactory()->create(
            config: $config,
            entriesKey: 'connections',
            missingException: static fn(string $key): MissingBroadcastingConfigException => new MissingBroadcastingConfigException($key),
            invalidException: static fn(string $key): InvalidBroadcastingConfigException => new InvalidBroadcastingConfigException($key),
        );
    }
}
