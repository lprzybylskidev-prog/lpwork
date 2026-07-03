<?php

declare(strict_types=1);

namespace Tests\support\validation;

use LPWork\Validation\FormRequest;

class StorePostFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'meta.published' => 'boolean',
        ];
    }
}
