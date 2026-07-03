<?php

declare(strict_types=1);

namespace Tests\support\console;

use RuntimeException;

final readonly class OutputStreams
{
    /**
     * @param resource $stdout
     * @param resource $stderr
     * @param resource $stdin
     */
    private function __construct(
        public mixed $stdout,
        public mixed $stderr,
        public mixed $stdin,
    ) {}

    public static function create(string $input = ''): self
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        $stdin = fopen('php://memory', 'w+');

        if ($stdout === false || $stderr === false || $stdin === false) {
            throw new RuntimeException('Could not open memory streams.');
        }

        fwrite($stdin, $input);
        rewind($stdin);

        return new self($stdout, $stderr, $stdin);
    }

    public function stdout(): string
    {
        return $this->content($this->stdout);
    }

    public function stderr(): string
    {
        return $this->content($this->stderr);
    }

    /**
     * @param resource $stream
     */
    private function content(mixed $stream): string
    {
        rewind($stream);

        $content = stream_get_contents($stream);

        if ($content === false) {
            throw new RuntimeException('Could not read console output stream.');
        }

        return $content;
    }
}
