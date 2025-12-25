<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

/**
 * Loads route definitions from PHP files.
 */
class RouteLoader implements Contract\RouteLoaderInterface
{
    /**
     * @var string
     */
    private string $appRoutesPath;

    /**
     * @var string|null
     */
    private ?string $builtinRoutesPath;

    /**
     * @param string      $appRoutesPath
     * @param string|null $builtinRoutesPath
     */
    public function __construct(string $appRoutesPath, ?string $builtinRoutesPath = null)
    {
        $this->appRoutesPath = $appRoutesPath;
        $this->builtinRoutesPath = $builtinRoutesPath;
    }

    /**
     * @return RouteCollection
     */
    public function load(): RouteCollection
    {
        $routes = new RouteCollection();

        if ($this->builtinRoutesPath !== null && \is_file($this->builtinRoutesPath)) {
            $this->includeFile($this->builtinRoutesPath, $routes);
        }

        if (\is_file($this->appRoutesPath)) {
            $this->includeFile($this->appRoutesPath, $routes);
        }

        return $routes;
    }

    /**
     * @param string          $file
     * @param RouteCollection $routes
     *
     * @return void
     */
    private function includeFile(string $file, RouteCollection $routes): void
    {
        $loader = static function (RouteCollection $routes, string $path): void {
            /** @psalm-suppress UnresolvableInclude */
            /** @phpstan-ignore-next-line */
            require $path;
        };

        $loader($routes, $file);
    }
}
