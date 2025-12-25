<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

use Psr\Container\ContainerInterface;
use LPwork\Http\Routing\Contract\RouteHandlerResolverInterface;

/**
 * Resolves route handler definitions into callables using the container when needed.
 */
final class RouteHandlerResolver implements RouteHandlerResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param mixed $handlerDefinition
     *
     * @return callable|null
     */
    public function resolve(mixed $handlerDefinition): callable|null
    {
        if (\is_callable($handlerDefinition) && !\is_string($handlerDefinition)) {
            return $handlerDefinition;
        }

        if (\is_array($handlerDefinition) && \count($handlerDefinition) === 2) {
            return $this->resolveArrayHandler($handlerDefinition);
        }

        if (\is_string($handlerDefinition)) {
            return $this->resolveStringHandler($handlerDefinition);
        }

        return null;
    }

    /**
     * @param array<int, mixed> $handlerDefinition
     *
     * @return callable|null
     */
    private function resolveArrayHandler(array $handlerDefinition): callable|null
    {
        [$classOrInstance, $method] = $handlerDefinition;

        if (\is_string($classOrInstance)) {
            if (!\class_exists($classOrInstance)) {
                return null;
            }

            $instance = $this->container->get($classOrInstance);
            $candidate = [$instance, $method];

            return \is_callable($candidate) ? $candidate : null;
        }

        $candidate = [$classOrInstance, $method];

        return \is_callable($candidate) ? $candidate : null;
    }

    /**
     * @param string $handlerDefinition
     *
     * @return callable|null
     */
    private function resolveStringHandler(string $handlerDefinition): callable|null
    {
        if (\str_contains($handlerDefinition, '@')) {
            [$class, $method] = \explode('@', $handlerDefinition, 2);

            if (!\class_exists($class)) {
                return null;
            }

            $instance = $this->container->get($class);
            $candidate = [$instance, $method];

            return \is_callable($candidate) ? $candidate : null;
        }

        if (!\class_exists($handlerDefinition)) {
            return \is_callable($handlerDefinition) ? $handlerDefinition : null;
        }

        $instance = $this->container->get($handlerDefinition);

        if (\is_callable($instance)) {
            return $instance;
        }

        if (\method_exists($instance, '__invoke')) {
            $candidate = [$instance, '__invoke'];

            return \is_callable($candidate) ? $candidate : null;
        }

        return null;
    }
}
