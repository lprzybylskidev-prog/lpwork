<?php

declare(strict_types=1);

namespace LPWork\Container;

use Closure;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Container\Exceptions\CircularDependencyException;
use LPWork\Container\Exceptions\InvalidBindingException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Resolves framework and application services through explicit bindings, aliases, tags, and autowiring.
 */
final class Container
{
    /**
     * @var array<string, array{concrete: string|Closure(self): mixed, shared: bool}>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * @var array<string, array<string, string|Closure(self): mixed>>
     */
    private array $contextualBindings = [];

    /**
     * @var array<string, list<string>>
     */
    private array $tags = [];

    /**
     * Registers a transient service binding; each resolution builds a fresh object unless the concrete manages reuse itself.
     *
     * @param string|Closure(self): mixed|null $concrete Class name or factory used to build the abstract service.
     */
    public function bind(string $abstract, string|Closure|null $concrete = null): void
    {
        $this->register($abstract, $concrete, shared: false);
    }

    /**
     * Registers a shared service binding; the first resolved object is reused for later resolutions.
     *
     * @param string|Closure(self): mixed|null $concrete Class name or factory used to build the abstract service.
     */
    public function singleton(string $abstract, string|Closure|null $concrete = null): void
    {
        $this->register($abstract, $concrete, shared: true);
    }

    /**
     * @param string|Closure(self): mixed|null $concrete
     */
    private function register(string $abstract, string|Closure|null $concrete, bool $shared): void
    {
        $abstract = $this->abstract($abstract);
        $concrete ??= $abstract;

        if (is_string($concrete)) {
            $concrete = $this->abstract($concrete);
            $this->assertConcreteCanBeResolved($abstract, $concrete);
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];

        unset($this->instances[$abstract]);
    }

    /**
     * Stores an already-created object as the resolved instance for an abstract service.
     */
    public function instance(string $abstract, object $instance): void
    {
        $abstract = $this->abstract($abstract);
        $this->instances[$abstract] = $instance;
    }

    /**
     * Adds an alternate name that resolves to the same abstract service binding.
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $this->abstract($abstract);
    }

    /**
     * Reports whether the container can resolve a service through a binding, instance, alias, or instantiable class.
     */
    public function has(string $id): bool
    {
        return $this->canResolve($this->abstract($id));
    }

    /**
     * Reports whether a service has been explicitly bound or registered as an instance.
     */
    public function isBound(string $id): bool
    {
        $id = $this->abstract($id);

        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * PSR-style service lookup that resolves and returns an object service.
     */
    public function get(string $id): object
    {
        return $this->make($id);
    }

    /**
     * Resolves a service by id, applying aliases, contextual bindings, shared instances, and constructor autowiring.
     */
    public function make(string $id): object
    {
        return $this->resolve($this->abstract($id), ResolutionContext::empty());
    }

    /**
     * Starts a contextual binding declaration for dependencies needed only when building a specific concrete class.
     */
    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $this->abstract($concrete));
    }

    /**
     * Assigns one or more service ids to a tag so they can be resolved as an ordered group.
     *
     * @param string|list<string> $abstracts Service ids to attach to the tag.
     */
    public function tag(string|array $abstracts, string $tag): void
    {
        foreach ((array) $abstracts as $abstract) {
            $this->tags[$tag][] = $this->abstract($abstract);
        }
    }

    /**
     * Resolves all services registered for a tag in registration order.
     *
     * @return list<object>
     */
    public function tagged(string $tag): array
    {
        $objects = [];

        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $objects[] = $this->make($abstract);
        }

        return $objects;
    }

    /**
     * Invokes a closure or object method while resolving missing parameters from the container.
     *
     * @param Closure|array{0: object|string, 1: string} $callback Callable to invoke; string targets are resolved first.
     * @param array<string, mixed> $parameters Explicit argument values keyed by parameter name.
     */
    public function call(Closure|array $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            [$target, $method] = $callback;
            $object = is_string($target) ? $this->make($target) : $target;
            $reflection = new ReflectionMethod($object, $method);

            return $reflection->invokeArgs($object, $this->resolveCallableParameters(
                owner: $object::class,
                reflection: $reflection,
                parameters: $parameters,
            ));
        }

        $reflection = new ReflectionFunction($callback);

        return $reflection->invokeArgs($this->resolveCallableParameters(
            owner: 'callable',
            reflection: $reflection,
            parameters: $parameters,
        ));
    }

    /**
     * Registers the implementation to inject for an abstract dependency while building one concrete class.
     *
     * @param string|Closure(self): mixed $implementation Class name or factory used only for the contextual dependency.
     */
    public function addContextualBinding(string $concrete, string $abstract, string|Closure $implementation): void
    {
        if (is_string($implementation)) {
            $implementation = $this->abstract($implementation);
            $this->assertConcreteCanBeResolved($abstract, $implementation);
        }

        $this->contextualBindings[$this->abstract($concrete)][$this->abstract($abstract)] = $implementation;
    }

    private function resolve(string $id, ResolutionContext $context): object
    {
        $id = $this->abstract($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $binding = $this->bindings[$id] ?? null;

        if ($binding === null) {
            return $this->build($id, $context);
        }

        $object = $this->buildConcrete($id, $binding['concrete'], $context);

        if ($binding['shared']) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    /**
     * @param string|Closure(self): mixed $concrete
     */
    private function buildConcrete(string $id, string|Closure $concrete, ResolutionContext $context): object
    {
        if ($concrete instanceof Closure) {
            $object = $concrete($this);

            if (!is_object($object)) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject($id);
            }

            return $object;
        }

        return $this->build($concrete, $context);
    }

    private function buildImplementation(string $id, string|Closure $implementation, ResolutionContext $context): object
    {
        if ($implementation instanceof Closure) {
            $object = $implementation($this);

            if (!is_object($object)) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject($id);
            }

            return $object;
        }

        return $this->resolve($implementation, $context);
    }

    private function build(string $id, ResolutionContext $context): object
    {
        if (!class_exists($id) && !interface_exists($id)) {
            throw CannotResolveDependencyException::classDoesNotExist($id);
        }

        $reflection = new ReflectionClass($id);

        if (!$reflection->isInstantiable()) {
            throw CannotResolveDependencyException::classIsNotInstantiable($id);
        }

        if ($context->contains($id)) {
            throw CircularDependencyException::fromChain($context->chainWith($id));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return $reflection->newInstance();
        }

        $arguments = [];
        $context = $context->push($id);

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($id, $parameter, $context);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $class, ReflectionParameter $parameter, ResolutionContext $context): mixed
    {
        $type = $parameter->getType();

        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw CannotResolveDependencyException::parameterHasNoType($class, $parameter->getName());
        }

        if (!$type instanceof ReflectionNamedType) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw CannotResolveDependencyException::parameterHasUnsupportedType($class, $parameter->getName());
        }

        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw CannotResolveDependencyException::parameterHasBuiltinType($class, $parameter->getName(), $type->getName());
        }

        $dependency = $type->getName();
        $contextual = $this->contextualBindings[$class][$this->abstract($dependency)] ?? null;

        if ($contextual !== null) {
            return $this->buildImplementation($dependency, $contextual, $context);
        }

        if (!$this->canResolve($dependency)) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw CannotResolveDependencyException::parameterBindingMissing($class, $parameter->getName(), $dependency);
        }

        return $this->resolve($dependency, $context);
    }

    private function assertConcreteCanBeResolved(string $abstract, string $concrete): void
    {
        if (!class_exists($concrete) && !interface_exists($concrete)) {
            throw InvalidBindingException::concreteClassDoesNotExist($abstract, $concrete);
        }

        if (!new ReflectionClass($concrete)->isInstantiable()) {
            throw InvalidBindingException::concreteIsNotInstantiable($abstract, $concrete);
        }
    }

    private function canResolve(string $id): bool
    {
        $id = $this->abstract($id);

        if (isset($this->bindings[$id]) || isset($this->instances[$id])) {
            return true;
        }

        if (!class_exists($id)) {
            return false;
        }

        return new ReflectionClass($id)->isInstantiable();
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<mixed>
     */
    private function resolveCallableParameters(string $owner, ReflectionFunctionAbstract $reflection, array $parameters): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $parameters)) {
                $arguments[] = $parameters[$parameter->getName()];

                continue;
            }

            $arguments[] = $this->resolveParameter($owner, $parameter, ResolutionContext::empty());
        }

        return $arguments;
    }

    private function abstract(string $id): string
    {
        $seen = [];

        while (isset($this->aliases[$id])) {
            if (isset($seen[$id])) {
                throw CircularDependencyException::fromChain(array_keys($seen));
            }

            $seen[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }
}
