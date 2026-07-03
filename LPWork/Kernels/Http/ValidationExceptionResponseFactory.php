<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Http\Redirect;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;
use LPWork\Responses\HttpResponse;
use LPWork\Validation\Exceptions\ValidationException;

/**
 * Creates validation exception response factory instances from framework configuration.
 */
final readonly class ValidationExceptionResponseFactory
{
    /**
     * Builds or returns make.
     */
    public function make(ValidationException $exception, ResponseFormat $format, ?HttpRequest $request): ?HttpResponse
    {
        if ($format === ResponseFormat::Json) {
            return $this->json($exception);
        }

        if ($request === null) {
            return null;
        }

        return $this->redirectBack($exception, $request);
    }

    private function json(ValidationException $exception): HttpResponse
    {
        return HttpResponse::json([
            'error' => [
                'status' => 422,
                'message' => 'Unprocessable Entity',
            ],
            'errors' => $exception->errors()->toArray(),
        ], statusCode: 422);
    }

    private function redirectBack(ValidationException $exception, HttpRequest $request): HttpResponse
    {
        $session = $request->session();
        $response = Redirect::back($request);

        Redirect::withErrors($response, $session, $this->formErrors($exception));
        Redirect::withInput($response, $session, $request->input());

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function formErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->errors()->toArray() as $field => $messages) {
            $this->setNestedValue($errors, $field, $messages);
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function setNestedValue(array &$values, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$values;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }
}
