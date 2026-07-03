<?php

declare(strict_types=1);

namespace Tests\support\view;

use LPWork\View\Contracts\ViewEngine;
use LPWork\View\ViewRenderContext;

final readonly class UppercaseViewEngine implements ViewEngine
{
    /**
     * @param array<string, mixed>|object $data
     */
    public function render(string $path, array|object $data, ViewRenderContext $context): string
    {
        $message = is_array($data) ? ($data['message'] ?? $path) : $path;

        return is_string($message) ? strtoupper($message) : $path;
    }
}
