<?php

declare(strict_types=1);

namespace Tests\support\exceptions;

use RuntimeException;

final class TestSupportException extends RuntimeException
{
    public static function memoryStreamCouldNotBeOpened(): self
    {
        return new self('Could not open memory stream.');
    }

    public static function expectedHttpResponse(string $actual): self
    {
        return new self(sprintf('Expected HttpResponse, got %s.', $actual));
    }

    public static function forcedHttpEmitFailure(): self
    {
        return new self('Http emit failed');
    }

    public static function httpClientDidNotCaptureResponse(): self
    {
        return new self('HTTP test client did not capture a response.');
    }

    public static function testResponseHasNoSession(): self
    {
        return new self('Test response has no attached session.');
    }

    public static function jsonCouldNotBeEncoded(string $message): self
    {
        return new self(sprintf('Could not encode test JSON body: %s', $message));
    }

    public static function temporaryDirectoryCouldNotBeCreated(string $path): self
    {
        return new self(sprintf('Could not create temporary test directory: %s', $path));
    }

    public static function testDirectoryCouldNotBeRead(string $path): self
    {
        return new self(sprintf('Could not read test directory: %s', $path));
    }

    public static function testFileCouldNotBeWritten(string $path): self
    {
        return new self(sprintf('Could not write test file: %s', $path));
    }

    public static function testFileCouldNotBeCopied(string $source, string $destination): self
    {
        return new self(sprintf('Could not copy test file from %s to %s.', $source, $destination));
    }

    public static function sourceTestPathDoesNotExist(string $path): self
    {
        return new self(sprintf('Source test path does not exist: %s', $path));
    }

    public static function expectedQueueRowArray(): self
    {
        return new self('Expected the first queue row to be an array.');
    }
}
