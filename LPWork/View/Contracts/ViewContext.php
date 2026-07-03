<?php

declare(strict_types=1);

namespace LPWork\View\Contracts;

use Stringable;

/**
 * Defines the contract for view context.
 */
interface ViewContext
{
    /**
     * Performs the e operation.
     */
    public function e(mixed $value): string;

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    public function t(string $key, array $parameters = [], ?string $locale = null): string;

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    public function text(string $text, array $parameters = [], ?string $locale = null): string;

    /**
     * @param array<string, mixed>|object $data
     */
    public function partial(string $name, array|object $data = []): string;

    /**
     * @param array<string, mixed>|object $data
     */
    public function include(string $name, array|object $data = []): void;

    /**
     * @param array<string, mixed>|object $data
     */
    public function layout(string $name, array|object $data = []): void;

    /**
     * Performs the start operation.
     */
    public function start(string $name): void;

    /**
     * Performs the end operation.
     */
    public function end(): void;

    /**
     * Performs the section operation.
     */
    public function section(string $name, string $default = ''): string;

    /**
     * Performs the content operation.
     */
    public function content(): string;
}
