<?php

declare(strict_types=1);

namespace LPWork\Routing;

use Closure;
use LPWork\Routing\Exceptions\InvalidRouteActionException;

/**
 * Represents the route action framework component.
 */
final readonly class RouteAction
{
    /**
     * Creates a new RouteAction instance.
     */
    public function __construct(
        private ?string $controller = null,
        private ?string $method = null,
        private ?Closure $closure = null,
    ) {
        if ($this->closure !== null) {
            return;
        }

        if ($this->controller === null || $this->controller === '' || $this->method === null || $this->method === '') {
            throw new InvalidRouteActionException();
        }
    }

    /**
     * @param array{0: string, 1: string} $action
     */
    public static function fromArray(array $action): self
    {
        return new self(controller: $action[0], method: $action[1]);
    }

    /**
     * Creates a RouteAction instance from from closure input.
     */
    public static function fromClosure(Closure $action): self
    {
        return new self(closure: $action);
    }

    /**
     * Reports whether is closure.
     */
    public function isClosure(): bool
    {
        return $this->closure !== null;
    }

    /**
     * Performs the closure operation.
     */
    public function closure(): ?Closure
    {
        return $this->closure;
    }

    /**
     * Performs the controller operation.
     */
    public function controller(): string
    {
        return $this->controller ?? '';
    }

    /**
     * Returns method.
     */
    public function method(): string
    {
        return $this->method ?? '';
    }
}
