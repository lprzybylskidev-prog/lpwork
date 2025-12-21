<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * Immutable session facade that propagates state updates.
 */
final class Session implements SessionInterface
{
    /**
     * @var SessionState
     */
    private SessionState $state;

    /**
     * @var SessionContext
     */
    private SessionContext $context;

    /**
     * @var SessionIdGeneratorInterface
     */
    private SessionIdGeneratorInterface $idGenerator;

    /**
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @param SessionState                $state
     * @param SessionContext              $context
     * @param SessionIdGeneratorInterface $idGenerator
     * @param ClockInterface              $clock
     */
    public function __construct(
        SessionState $state,
        SessionContext $context,
        SessionIdGeneratorInterface $idGenerator,
        ClockInterface $clock,
    ) {
        $this->state = $state;
        $this->context = $context;
        $this->idGenerator = $idGenerator;
        $this->clock = $clock;
    }

    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return $this->state->id();
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->state->has($key);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->state->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->state->all();
    }

    /**
     * @inheritDoc
     */
    public function with(string $key, mixed $value): SessionInterface
    {
        $newState = $this->state->with(
            $key,
            $value,
            $this->clock->now()->getTimestamp(),
        );

        return $this->refresh($newState);
    }

    /**
     * @inheritDoc
     */
    public function without(string $key): SessionInterface
    {
        $newState = $this->state->without(
            $key,
            $this->clock->now()->getTimestamp(),
        );

        return $this->refresh($newState);
    }

    /**
     * @inheritDoc
     */
    public function clear(): SessionInterface
    {
        $newState = $this->state->cleared($this->clock->now()->getTimestamp());

        return $this->refresh($newState);
    }

    /**
     * @inheritDoc
     */
    public function regenerateId(): SessionInterface
    {
        $newId = $this->idGenerator->generate();
        $newState = $this->state->withId(
            $newId,
            $this->clock->now()->getTimestamp(),
        );

        return $this->refresh($newState);
    }

    /**
     * Updates shared state and returns new session view.
     *
     * @param SessionState $state
     *
     * @return SessionInterface
     */
    private function refresh(SessionState $state): SessionInterface
    {
        $this->context->update($state);

        return new self(
            $state,
            $this->context,
            $this->idGenerator,
            $this->clock,
        );
    }
}
