<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_key_exists;

/**
 * Represents the input framework component.
 */
final class Input
{
    /**
     * @var array<int, string>
     */
    private readonly array $argv;

    /**
     * @var list<string>
     */
    private array $arguments = [];

    /**
     * @var array<string, string|bool|int|list<string|bool|int>>
     */
    private array $options = [];

    /**
     * @param array<int, string> $argv
     */
    public function __construct(
        array $argv,
        InputParser $parser = new InputParser(),
        ConsoleArgvNormalizer $normalizer = new ConsoleArgvNormalizer(),
    ) {
        $this->argv = $normalizer->normalize($argv);
        $parsed = $parser->parse($this->argv);
        $this->arguments = $parsed['arguments'];
        $this->options = $parsed['options'];
    }

    /**
     * Performs the script operation.
     */
    public function script(): string
    {
        return $this->argv[0] ?? '';
    }

    /**
     * Performs the command operation.
     */
    public function command(): ?string
    {
        return $this->argv[1] ?? null;
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * Performs the argument operation.
     */
    public function argument(int $index): ?string
    {
        return $this->arguments()[$index] ?? null;
    }

    /**
     * Reports whether has command.
     */
    public function hasCommand(): bool
    {
        return array_key_exists(1, $this->argv) && $this->argv[1] !== '';
    }

    /**
     * Reports whether has option.
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @return string|bool|int|list<string|bool|int>|null
     */
    public function option(string $name): string|bool|int|array|null
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @return list<string|bool|int>
     */
    public function optionValues(string $name): array
    {
        $value = $this->option($name);

        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * @return array<string, string|bool|int|list<string|bool|int>>
     */
    public function options(): array
    {
        return $this->options;
    }

}
