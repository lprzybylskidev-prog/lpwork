<?php
declare(strict_types=1);

namespace LPwork\Provider;

use LPwork\Provider\Contract\ProviderInterface;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Instantiates providers using a PSR-11 container with autowiring.
 */
class ProviderFactory
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
     * @param class-string<ProviderInterface> $providerClass
     *
     * @return ProviderInterface
     */
    public function create(string $providerClass): ProviderInterface
    {
        if (!\is_subclass_of($providerClass, ProviderInterface::class)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Provider "%s" must implement %s.',
                    $providerClass,
                    ProviderInterface::class,
                ),
            );
        }

        $reflection = new ReflectionClass($providerClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            /** @var ProviderInterface $provider */
            $provider = new $providerClass();

            return $provider;
        }

        /** @var ProviderInterface $provider */
        $provider = $this->container->get($providerClass);

        return $provider;
    }
}
