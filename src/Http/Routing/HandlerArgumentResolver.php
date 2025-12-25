<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

use LPwork\Http\Exception\InvalidRouteArgumentsException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves route handler arguments using reflection and route parameters.
 */
final class HandlerArgumentResolver
{
    /**
     * @var \Psr\Container\ContainerInterface|null
     */
    private ?\Psr\Container\ContainerInterface $container;

    /**
     * @param \Psr\Container\ContainerInterface|null $container
     */
    public function __construct(?\Psr\Container\ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param callable               $handler
     * @param ServerRequestInterface   $request
     * @param array<string, string>    $routeParams
     *
     * @return array<int, mixed>
     */
    public function resolveArguments(
        callable $handler,
        ServerRequestInterface $request,
        array $routeParams,
    ): array {
        $reflection = $this->createReflection($handler);

        if ($reflection === null) {
            return [$request];
        }

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();

                if (\is_a($className, ServerRequestInterface::class, true)) {
                    $arguments[] = $request;
                    continue;
                }

                if ($this->container !== null && \class_exists($className)) {
                    try {
                        $arguments[] = $this->container->get($className);
                        continue;
                    } catch (\Throwable) {
                        // fall through to other resolution paths
                    }
                }
            }

            if (\array_key_exists($name, $routeParams)) {
                $arguments[] = $routeParams[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidRouteArgumentsException(
                \sprintf('Missing route parameter: %s', $name),
            );
        }

        if ($arguments === []) {
            return [$request];
        }

        return $arguments;
    }

    /**
     * @param callable $handler
     *
     * @return \ReflectionFunction|\ReflectionMethod|null
     */
    private function createReflection(callable $handler): \ReflectionFunction|\ReflectionMethod|null
    {
        if (\is_array($handler) && \count($handler) === 2) {
            return new \ReflectionMethod($handler[0], (string) $handler[1]);
        }

        if (\is_object($handler) && !$handler instanceof \Closure) {
            return new \ReflectionMethod($handler, '__invoke');
        }

        if ($handler instanceof \Closure) {
            return new \ReflectionFunction($handler);
        }

        if (\is_string($handler)) {
            return new \ReflectionFunction($handler);
        }

        return null;
    }
}
