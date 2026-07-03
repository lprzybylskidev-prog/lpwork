<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use Closure;
use LPWork\Kernels\Http\Exceptions\FormRequestFactoryNotRegisteredException;
use LPWork\Requests\HttpRequest;
use LPWork\Routing\Exceptions\ClosureRouteParameterException;
use LPWork\Routing\RouteMatch;
use LPWork\Validation\FormRequest;
use LPWork\Validation\FormRequestFactory;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Resolves route action parameters resolver values into runtime objects.
 */
final readonly class RouteActionParametersResolver
{
    /**
     * @param (Closure(): FormRequestFactory)|null $formRequests
     */
    public function __construct(
        private ?Closure $formRequests = null,
    ) {}

    /**
     * @param Closure|array{0: object|string, 1: string} $callback
     *
     * @return array<string, mixed>
     */
    public function resolve(Closure|array $callback, HttpRequest $request, RouteMatch $match, bool $failOnMissingRouteValue = false): array
    {
        $parameters = [];
        $remainingRouteParameters = $match->parameters();

        foreach ($this->reflection($callback)->getParameters() as $parameter) {
            $name = $parameter->getName();

            $formRequest = $this->formRequestClass($parameter);
            if ($formRequest !== null) {
                $parameters[$name] = $this->formRequests()->make($formRequest, $request);

                continue;
            }

            if ($this->isHttpRequest($parameter)) {
                $parameters[$name] = $request;

                continue;
            }

            if (array_key_exists($name, $remainingRouteParameters)) {
                $parameters[$name] = $remainingRouteParameters[$name];
                unset($remainingRouteParameters[$name]);

                continue;
            }

            if ($this->expectsRouteValue($parameter) && $remainingRouteParameters !== []) {
                $parameters[$name] = array_shift($remainingRouteParameters);

                continue;
            }

            if ($failOnMissingRouteValue && $this->expectsRouteValue($parameter) && !$parameter->isDefaultValueAvailable()) {
                throw ClosureRouteParameterException::cannotResolve($match->route()->path(), $name);
            }
        }

        return $parameters;
    }

    /**
     * @param Closure|array{0: object|string, 1: string} $callback
     */
    private function reflection(Closure|array $callback): ReflectionFunctionAbstract
    {
        if ($callback instanceof Closure) {
            return new ReflectionFunction($callback);
        }

        return new ReflectionMethod($callback[0], $callback[1]);
    }

    private function isHttpRequest(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && is_a($type->getName(), HttpRequest::class, true);
    }

    /**
     * @return class-string<FormRequest>|null
     */
    private function formRequestClass(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || !is_a($type->getName(), FormRequest::class, true)) {
            return null;
        }

        $formRequest = $type->getName();

        return $formRequest;
    }

    private function formRequests(): FormRequestFactory
    {
        if ($this->formRequests === null) {
            throw new FormRequestFactoryNotRegisteredException();
        }

        return ($this->formRequests)();
    }

    private function expectsRouteValue(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        return $type === null || ($type instanceof ReflectionNamedType && $type->isBuiltin());
    }
}
