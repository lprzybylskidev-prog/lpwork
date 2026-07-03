<?php

declare(strict_types=1);

namespace LPWork\Routing;

use function array_map;

use Closure;

use function implode;

use LPWork\Console\ConsoleTable;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;
use LPWork\Console\Output;

use function sprintf;

/**
 * Renders route list renderer output.
 */
final class RouteListRenderer
{
    /**
     * Creates a new RouteListRenderer instance.
     */
    public function __construct(
        private readonly ConsoleTableRenderer $tables = new ConsoleTableRenderer(),
    ) {}

    /**
     * @param list<Route> $routes
     */
    public function render(array $routes, Output $output): void
    {
        if ($routes === []) {
            $output->writelnFormatted('No routes registered.', ConsoleColor::Gray);

            return;
        }

        $rows = array_map(fn(Route $route): RouteListRow => RouteListRow::fromRoute($route), $routes);

        $output->writelnFormatted('Registered routes:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(
            ['Method', 'URI', 'Name', 'Action', 'Middleware'],
            array_map(static fn(RouteListRow $row): array => $row->columns(), $rows),
        ), $output);
    }
}

/**
 * Represents the route list row framework component.
 */
final readonly class RouteListRow
{
    private function __construct(
        public string $method,
        public string $uri,
        public string $name,
        public string $action,
        public string $middleware,
    ) {}

    /**
     * Creates a RouteListRow instance from from route input.
     */
    public static function fromRoute(Route $route): self
    {
        return new self(
            method: implode('|', $route->methods()),
            uri: $route->path(),
            name: $route->name() ?? '-',
            action: self::action($route->action()),
            middleware: $route->middlewareList() === [] ? '-' : implode(',', $route->middlewareList()),
        );
    }

    private static function action(RouteAction $action): string
    {
        if ($action->isClosure()) {
            return Closure::class;
        }

        return sprintf('%s@%s', $action->controller(), $action->method());
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return [
            $this->method,
            $this->uri,
            $this->name,
            $this->action,
            $this->middleware,
        ];
    }
}
