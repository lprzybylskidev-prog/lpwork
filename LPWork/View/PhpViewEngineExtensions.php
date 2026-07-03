<?php

declare(strict_types=1);

namespace LPWork\View;

use Closure;
use LPWork\View\Exceptions\InvalidPhpViewExtensionException;

/**
 * Represents the php view engine extensions framework component.
 */
final class PhpViewEngineExtensions
{
    /**
     * @var array<string, mixed>
     */
    private array $globals = [];

    /**
     * @var array<string, Closure>
     */
    private array $functions = [];

    public function global(string $name, mixed $value): void
    {
        $this->assertName($name);

        $this->globals[$name] = $value;
    }

    public function function(string $name, Closure $function): void
    {
        $this->assertName($name);

        $this->functions[$name] = $function;
    }

    /**
     * @return array<string, mixed>
     */
    public function globals(): array
    {
        return $this->globals;
    }

    /**
     * @return array<string, Closure>
     */
    public function functions(): array
    {
        return $this->functions;
    }

    /**
     * @return array<string, mixed>
     */
    public function variables(): array
    {
        return [...$this->globals, ...$this->functions];
    }

    private function assertName(string $name): void
    {
        if ($name === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw InvalidPhpViewExtensionException::invalidName($name);
        }

        if ($name === 'view' || $name === 'data') {
            throw InvalidPhpViewExtensionException::reservedName($name);
        }
    }
}
