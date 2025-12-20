<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

/**
 * Holds mutable session lifecycle context for a request.
 */
final class SessionContext
{
    /**
     * @var SessionState
     */
    private SessionState $state;

    /**
     * @param SessionState $state
     */
    public function __construct(SessionState $state)
    {
        $this->state = $state;
    }

    /**
     * @return SessionState
     */
    public function state(): SessionState
    {
        return $this->state;
    }

    /**
     * @param SessionState $state
     *
     * @return void
     */
    public function update(SessionState $state): void
    {
        $this->state = $state;
    }
}
