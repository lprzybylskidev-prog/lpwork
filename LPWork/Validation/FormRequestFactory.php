<?php

declare(strict_types=1);

namespace LPWork\Validation;

use LPWork\Container\Container;
use LPWork\Requests\HttpRequest;
use LPWork\Validation\Exceptions\InvalidFormRequestException;

/**
 * Creates form request factory instances from framework configuration.
 */
final readonly class FormRequestFactory
{
    /**
     * Creates a new FormRequestFactory instance.
     */
    public function __construct(
        private Container $container,
        private Validator $validator,
    ) {}

    /**
     * @param class-string $formRequest
     */
    public function make(string $formRequest, HttpRequest $request): FormRequest
    {
        $resolved = $this->container->make($formRequest);

        if (!$resolved instanceof FormRequest) {
            throw InvalidFormRequestException::resolvedObject($formRequest);
        }

        $resolved->initializeRequest($request);

        $result = $this->validator->validate($this->validationInput($request), $resolved->rules())->throw();
        $resolved->initializeValidated($result->validated());

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function validationInput(HttpRequest $request): array
    {
        return array_replace($request->input(), $request->files());
    }
}
