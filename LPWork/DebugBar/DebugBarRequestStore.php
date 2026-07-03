<?php

declare(strict_types=1);

namespace LPWork\DebugBar;

use JsonException;
use LPWork\Filesystem\Filesystem;
use Throwable;

/**
 * Represents the debug bar request store framework component.
 */
final readonly class DebugBarRequestStore
{
    private const MAX_REQUESTS = 50;

    /**
     * Creates a new DebugBarRequestStore instance.
     */
    public function __construct(
        private string $basePath,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function put(string $session, string $id, array $payload): void
    {
        if (is_file(rtrim($this->basePath, '/'))) {
            return;
        }

        $path = $this->requestPath($session, $id);
        $payload['id'] = $id;
        $payload['session'] = $session;
        $payload['recordedAt'] = time();

        try {
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return;
        }

        try {
            $this->filesystem->write($path, $encoded);
            $this->prune($session);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $session, string $id): ?array
    {
        if (is_file(rtrim($this->basePath, '/'))) {
            return null;
        }

        $path = $this->requestPath($session, $id);

        try {
            if (!$this->filesystem->isFile($path)) {
                return null;
            }

            $decoded = json_decode($this->filesystem->read($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $record = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $record[$key] = $value;
            }
        }

        return $record;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(string $session): array
    {
        if (is_file(rtrim($this->basePath, '/'))) {
            return [];
        }

        $records = [];

        try {
            $files = $this->filesystem->files($this->sessionPath($session) . '/*.json');
        } catch (Throwable) {
            return [];
        }

        foreach ($files as $file) {
            $record = $this->get($session, basename($file, '.json'));

            if ($record !== null) {
                $records[] = $record;
            }
        }

        usort($records, static fn(array $left, array $right): int => ($left['recordedAt'] ?? 0) <=> ($right['recordedAt'] ?? 0));

        return $records;
    }

    private function prune(string $session): void
    {
        $records = $this->list($session);

        while (count($records) > self::MAX_REQUESTS) {
            $record = array_shift($records);
            $id = is_string($record['id'] ?? null) ? $record['id'] : null;

            if ($id !== null) {
                $this->filesystem->delete($this->requestPath($session, $id));
            }
        }
    }

    private function requestPath(string $session, string $id): string
    {
        return $this->sessionPath($session) . '/' . $this->safeId($id) . '.json';
    }

    private function sessionPath(string $session): string
    {
        return rtrim($this->basePath, '/') . '/sessions/' . $this->safeId($session);
    }

    private function safeId(string $value): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $value);

        return is_string($safe) && $safe !== '' ? $safe : 'debug';
    }
}
