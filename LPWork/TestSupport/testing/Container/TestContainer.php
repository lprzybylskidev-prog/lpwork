<?php

declare(strict_types=1);

namespace Tests\support\testing\Container;

use Closure;
use LPWork\Container\Container;
use LPWork\Foundation\Application;
use PHPUnit\Framework\Assert;
use ReflectionClass;

final readonly class TestContainer
{
    public function __construct(
        private Container $container = new Container(),
    ) {}

    public static function create(): self
    {
        return new self();
    }

    public static function forApplication(Application $application): self
    {
        return new self($application->container());
    }

    public function container(): Container
    {
        return $this->container;
    }

    /**
     * @param string|Closure(Container): mixed|null $concrete
     */
    public function bind(string $abstract, string|Closure|null $concrete = null): self
    {
        $this->container->bind($abstract, $concrete);

        return $this;
    }

    /**
     * @param string|Closure(Container): mixed|null $concrete
     */
    public function singleton(string $abstract, string|Closure|null $concrete = null): self
    {
        $this->container->singleton($abstract, $concrete);

        return $this;
    }

    public function instance(string $abstract, object $instance): self
    {
        $this->container->instance($abstract, $instance);

        return $this;
    }

    /**
     * @param string|Closure(Container): mixed $implementation
     */
    public function contextual(string $concrete, string $abstract, string|Closure $implementation): self
    {
        $this->container
            ->when($concrete)
            ->needs($abstract)
            ->give($implementation);

        return $this;
    }

    /**
     * @param class-string<object> $expectedClass
     */
    public function assertResolvesTo(string $abstract, string $expectedClass): self
    {
        Assert::assertInstanceOf($expectedClass, $this->container->make($abstract));

        return $this;
    }

    public function assertResolvesSame(string $abstract, object $expected): self
    {
        Assert::assertSame($expected, $this->container->make($abstract), sprintf('Container entry [%s] did not resolve to the expected instance.', $abstract));

        return $this;
    }

    /**
     * @param Closure(object): void $assertions
     */
    public function assertResolvesWith(string $abstract, Closure $assertions): self
    {
        $assertions($this->container->make($abstract));

        return $this;
    }

    /**
     * @param class-string<object> $expectedClass
     */
    public function assertContextualDependency(string $concrete, string $property, string $expectedClass): self
    {
        $resolved = $this->container->make($concrete);
        $reflection = new ReflectionClass($resolved);

        Assert::assertTrue($reflection->hasProperty($property), sprintf('Resolved [%s] does not expose property [%s].', $concrete, $property));

        $dependency = $reflection->getProperty($property)->getValue($resolved);

        Assert::assertInstanceOf($expectedClass, $dependency);

        return $this;
    }
}
