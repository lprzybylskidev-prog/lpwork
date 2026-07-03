<?php

declare(strict_types=1);

namespace LPWork\View\Contracts;

use LPWork\View\ViewRenderContext;

/**
 * Defines the contract for view engine.
 */
interface ViewEngine
{
    /**
     * @param array<string, mixed>|object $data
     */
    public function render(string $path, array|object $data, ViewRenderContext $context): string;
}
