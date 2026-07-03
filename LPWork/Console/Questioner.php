<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_key_exists;
use function fgets;
use function implode;

use LPWork\Console\Exceptions\ConsoleInputReadException;
use LPWork\Console\Exceptions\InvalidChoiceException;

use function strtolower;
use function trim;

/**
 * Represents the questioner framework component.
 */
final class Questioner
{
    /**
     * @param resource $input
     */
    public function __construct(
        private readonly Output $output,
        private readonly mixed $input,
    ) {}

    /**
     * Performs the terminal operation.
     */
    public static function terminal(Output $output): self
    {
        return new self($output, STDIN);
    }

    /**
     * Performs the ask operation.
     */
    public function ask(string $question, ?string $default = null): string
    {
        $this->output->write($this->questionLabel($question, $default));

        $answer = $this->readAnswer();

        if ($answer === '' && $default !== null) {
            return $default;
        }

        return $answer;
    }

    /**
     * Performs the confirm operation.
     */
    public function confirm(string $question, bool $default = false): bool
    {
        $hint = $default ? 'Y/n' : 'y/N';
        $this->output->write("{$question} [{$hint}]: ");

        $answer = strtolower($this->readAnswer());

        if ($answer === '') {
            return $default;
        }

        return $answer === 'y' || $answer === 'yes';
    }

    /**
     * @param non-empty-array<string, string> $choices
     */
    public function choice(string $question, array $choices, ?string $default = null): string
    {
        if ($default !== null && !array_key_exists($default, $choices)) {
            throw new InvalidChoiceException($default);
        }

        $availableChoices = implode('/', array_keys($choices));
        $suffix = $default === null ? '' : " [{$default}]";

        $this->output->write("{$question} ({$availableChoices}){$suffix}: ");

        $answer = $this->readAnswer();

        if ($answer === '' && $default !== null) {
            return $default;
        }

        if (!array_key_exists($answer, $choices)) {
            throw new InvalidChoiceException($answer);
        }

        return $answer;
    }

    private function questionLabel(string $question, ?string $default): string
    {
        if ($default === null) {
            return "{$question}: ";
        }

        return "{$question} [{$default}]: ";
    }

    private function readAnswer(): string
    {
        $answer = fgets($this->input);

        if ($answer === false) {
            throw new ConsoleInputReadException();
        }

        return trim($answer);
    }
}
