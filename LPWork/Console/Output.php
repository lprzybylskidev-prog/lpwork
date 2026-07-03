<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_map;
use function fwrite;
use function implode;

use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;
use LPWork\Console\Exceptions\ConsoleOutputWriteException;

/**
 * Represents the output framework component.
 */
final class Output
{
    /**
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(
        private readonly mixed $stdout,
        private readonly mixed $stderr,
        private readonly bool $decorated = true,
    ) {}

    /**
     * Performs the terminal operation.
     */
    public static function terminal(): self
    {
        return new self(STDOUT, STDERR);
    }

    /**
     * Registers or stores write.
     */
    public function write(string $message): void
    {
        $this->writeTo($this->stdout, $message);
    }

    /**
     * Registers or stores writeln.
     */
    public function writeln(string $message = ''): void
    {
        $this->write($message . "\n");
    }

    /**
     * Performs the error operation.
     */
    public function error(string $message): void
    {
        $this->writeTo($this->stderr, $message . "\n");
    }

    /**
     * Registers or stores write error.
     */
    public function writeError(string $message): void
    {
        $this->writeTo($this->stderr, $message);
    }

    /**
     * @param list<ConsoleStyle> $styles
     */
    public function format(
        string $message,
        ?ConsoleColor $foreground = null,
        ?ConsoleColor $background = null,
        array $styles = [],
    ): string {
        if (!$this->decorated) {
            return $message;
        }

        $codes = array_map(
            static fn(ConsoleStyle $style): int => $style->value,
            $styles,
        );

        if ($foreground !== null) {
            $codes = [...$codes, ...$foreground->foregroundCodes()];
        }

        if ($background !== null) {
            $codes = [...$codes, ...$background->backgroundCodes()];
        }

        if ($codes === []) {
            return $message;
        }

        return "\033[" . implode(';', $codes) . "m{$message}\033[0m";
    }

    /**
     * @param list<ConsoleStyle> $styles
     */
    public function writeFormatted(
        string $message,
        ?ConsoleColor $foreground = null,
        ?ConsoleColor $background = null,
        array $styles = [],
    ): void {
        $this->write($this->format($message, $foreground, $background, $styles));
    }

    /**
     * @param list<ConsoleStyle> $styles
     */
    public function writelnFormatted(
        string $message,
        ?ConsoleColor $foreground = null,
        ?ConsoleColor $background = null,
        array $styles = [],
    ): void {
        $this->writeln($this->format($message, $foreground, $background, $styles));
    }

    /**
     * @param resource $stream
     */
    private function writeTo(mixed $stream, string $message): void
    {
        if (fwrite($stream, $message) === false) {
            throw new ConsoleOutputWriteException();
        }
    }
}
