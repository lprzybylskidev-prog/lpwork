<?php

declare(strict_types=1);

namespace Tests\support\security;

use RuntimeException;

final readonly class EnvironmentApplicationKeyFile
{
    public function __construct(
        private string $path,
    ) {}

    public function key(): string
    {
        $content = file_get_contents($this->path);

        if ($content === false) {
            throw new RuntimeException('Could not read test environment file.');
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);

        if ($lines === false) {
            throw new RuntimeException('Could not split test environment file.');
        }

        foreach ($lines as $line) {
            if (preg_match('/^APP_KEY=(.*)$/', $line, $matches) === 1) {
                return trim($matches[1], '"\'');
            }
        }

        return '';
    }
}
