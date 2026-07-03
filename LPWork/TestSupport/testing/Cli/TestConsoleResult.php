<?php

declare(strict_types=1);

namespace Tests\support\testing\Cli;

use PHPUnit\Framework\Assert;

final readonly class TestConsoleResult
{
    public function __construct(
        private int $exitCode,
        private string $stdout,
        private string $stderr,
    ) {}

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function stdout(): string
    {
        return $this->stdout;
    }

    public function stderr(): string
    {
        return $this->stderr;
    }

    public function assertExitCode(int $exitCode): self
    {
        Assert::assertSame($exitCode, $this->exitCode, 'Unexpected console exit code.');

        return $this;
    }

    public function assertSuccessful(): self
    {
        return $this->assertExitCode(0);
    }

    public function assertFailed(): self
    {
        Assert::assertNotSame(0, $this->exitCode, 'Console command exited successfully.');

        return $this;
    }

    public function assertStdout(string $output): self
    {
        Assert::assertSame($output, $this->stdout, 'Unexpected console stdout.');

        return $this;
    }

    public function assertStderr(string $output): self
    {
        Assert::assertSame($output, $this->stderr, 'Unexpected console stderr.');

        return $this;
    }

    public function assertNoStdout(): self
    {
        return $this->assertStdout('');
    }

    public function assertNoStderr(): self
    {
        return $this->assertStderr('');
    }

    public function assertStdoutContains(string $text): self
    {
        Assert::assertStringContainsString($text, $this->stdout);

        return $this;
    }

    public function assertStderrContains(string $text): self
    {
        Assert::assertStringContainsString($text, $this->stderr);

        return $this;
    }
}
