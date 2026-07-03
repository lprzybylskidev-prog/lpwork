<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Routing\RouteCollection;
use LPWork\Routing\RouteListRenderer;

/**
 * Handles the route list command console command.
 */
final readonly class RouteListCommand implements Command
{
    /**
     * Creates a new RouteListCommand instance.
     */
    public function __construct(
        private RouteCollection $routes,
        private RouteListRenderer $renderer = new RouteListRenderer(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'route:list';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Display registered application routes.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->renderer->render($this->routes->all(), $output);

        return 0;
    }
}
