# Validation

LPWork validation can be used directly through `Validator` or through `FormRequest` classes that are resolved for controller actions.

## Direct Validation

`Validator` validates input arrays and returns a result with validated data and errors:

```php
$result = $validator->validate($request->input(), [
    'title' => 'required|string|max:120',
    'email' => 'nullable|email',
]);

$result->throw();
$data = $result->validated();
```

Use this style for small validation boundaries or service-level validation.

## Form Requests

Use a form request when validation belongs to an HTTP action:

```php
use LPWork\Validation\FormRequest;

final class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:120',
            'body' => 'required|string',
        ];
    }
}
```

Controller actions can depend on the form request:

```php
final readonly class PostController
{
    public function store(StorePostRequest $request): HttpResponse
    {
        $title = $request->string('title');

        return HttpResponse::created('/posts/123');
    }
}
```

Form requests expose `validated()`, `validatedValue()`, typed accessors such as `string()` and `integer()`, request metadata, headers, input, and session access.

Form requests do not have an authorization hook. Put authorization in middleware, policies, explicit services, or controller orchestration as appropriate.

## Rule Syntax

Rules can be declared as pipe-delimited strings, arrays of strings, or `ValidationRule` instances:

```php
[
    'title' => 'required|string|max:120',
    'tags' => ['sometimes', 'array', 'max_items:5'],
    'published_at' => 'nullable|date',
]
```

Common built-in rules include:

- Presence and optionality: `required`, `sometimes`, `nullable`.
- Types and formats: `string`, `integer`, `numeric`, `boolean`, `array`, `list`, `assoc`, `email`, `url`, `uuid`, `json`, `ip`, `ipv4`, `ipv6`.
- String/content: `alpha`, `alpha_num`, `alpha_dash`, `lowercase`, `uppercase`, `starts_with`, `ends_with`, `contains`, `regex`.
- Size and comparison: `min`, `max`, `between`, `size`, `count`, `gt`, `gte`, `lt`, `lte`, `same`, `different`.
- Inclusion: `in`, `not_in`, `distinct`, `confirmed`.
- Date: `date`, `date_format`, `before`, `before_or_equal`, `after`, `after_or_equal`.
- Files: `file`, `image`, `mimes`, `extensions`, `file_size`, `min_file_size`, `max_file_size`, `dimensions`.
- Arrays: `required_array_keys`, `min_items`, `max_items`.

Parameters use colon syntax such as `max:120`, `in:draft,published`, or `date_format:Y-m-d`.

## Custom Rules

Custom validation rules implement `LPWork\Validation\Contracts\ValidationRule` and should be registered through a provider extending the framework validation rules provider.

Rules should return `null` when valid or a `ValidationMessage` when invalid. Use translation keys and parameters instead of hard-coded user-facing strings.

## Validation Failures

Validation failures throw `ValidationException` when requested by the result or when form-request validation fails during controller dispatch. HTTP exception formatting handles the response shape for normal web/API flows.

Tests should assert the observable behavior: redirect/session errors for web flows, JSON error responses for API flows, or thrown validation exceptions at service boundaries.
