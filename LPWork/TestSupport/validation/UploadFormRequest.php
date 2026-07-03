<?php

declare(strict_types=1);

namespace Tests\support\validation;

use LPWork\Validation\FormRequest;

final class UploadFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'avatar' => 'required|file|image|extensions:png',
        ];
    }
}
