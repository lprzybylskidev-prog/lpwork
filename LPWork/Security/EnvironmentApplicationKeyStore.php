<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Filesystem\Exceptions\FileReadException;
use LPWork\Filesystem\Exceptions\FileWriteException;
use LPWork\Filesystem\Filesystem;
use LPWork\Security\Exceptions\ApplicationKeyStoreException;

/**
 * Represents the environment application key store framework component.
 */
final readonly class EnvironmentApplicationKeyStore
{
    /**
     * Creates a new EnvironmentApplicationKeyStore instance.
     */
    public function __construct(
        private string $path,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Returns current.
     */
    public function current(): string
    {
        foreach ($this->lines() as $line) {
            if (preg_match('/^\s*APP_KEY\s*=\s*(.*)$/', $line, $matches) !== 1) {
                continue;
            }

            return $this->parseValue($matches[1]);
        }

        return '';
    }

    /**
     * Registers or stores write.
     */
    public function write(string $key): void
    {
        ApplicationKey::fromString($key);

        $lines = $this->lines();
        $updated = false;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*APP_KEY\s*=/', $line) !== 1) {
                continue;
            }

            $lines[$index] = 'APP_KEY=' . $key;
            $updated = true;

            break;
        }

        if (!$updated) {
            $lines[] = 'APP_KEY=' . $key;
        }

        $content = implode("\n", $lines);

        if (!str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        try {
            $this->filesystem->write($this->path, $content);
        } catch (FileWriteException) {
            throw ApplicationKeyStoreException::couldNotWrite($this->path);
        }
    }

    /**
     * @return list<string>
     */
    private function lines(): array
    {
        if (!$this->filesystem->isFile($this->path)) {
            throw ApplicationKeyStoreException::missingFile($this->path);
        }

        if (!$this->filesystem->isReadable($this->path)) {
            throw ApplicationKeyStoreException::unreadableFile($this->path);
        }

        try {
            $content = $this->filesystem->read($this->path);
        } catch (FileReadException) {
            throw ApplicationKeyStoreException::couldNotRead($this->path);
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);

        if ($lines === false) {
            throw ApplicationKeyStoreException::couldNotRead($this->path);
        }

        if ($lines !== [] && $lines[count($lines) - 1] === '') {
            array_pop($lines);
        }

        return $lines;
    }

    private function parseValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $firstChar = $value[0];
        $lastChar = $value[strlen($value) - 1];

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $quote = $firstChar;

            return str_replace('\\' . $quote, $quote, substr($value, 1, -1));
        }

        return $value;
    }
}
