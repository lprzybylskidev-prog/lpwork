<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Debug;

use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * Represents the debug exception inspector framework component.
 */
final readonly class DebugExceptionInspector
{
    /**
     * Creates a new DebugExceptionInspector instance.
     */
    public function __construct(
        private string $applicationPath,
        private SourceSnippetReader $source = new SourceSnippetReader(),
    ) {}

    /**
     * Performs the inspect operation.
     */
    public function inspect(Throwable $throwable): DebugExceptionView
    {
        $frames = $this->frames($throwable);
        $code = $throwable->getCode();

        return new DebugExceptionView(
            name: $throwable::class,
            nameParts: explode('\\', $throwable::class),
            message: $throwable->getMessage(),
            code: is_int($code) ? $code : null,
            file: $throwable->getFile(),
            line: $throwable->getLine(),
            frames: $frames,
            frameCounts: $this->frameCounts($frames),
            previousExceptions: $this->previousExceptions($throwable),
        );
    }

    /**
     * @return list<DebugPreviousException>
     */
    private function previousExceptions(Throwable $throwable): array
    {
        $previous = [];
        $current = $throwable->getPrevious();

        while ($current instanceof Throwable) {
            $frames = $this->frames($current);
            $code = $current->getCode();

            $previous[] = new DebugPreviousException(
                index: count($previous),
                name: $current::class,
                nameParts: explode('\\', $current::class),
                message: $current->getMessage(),
                code: is_int($code) ? $code : null,
                file: $current->getFile(),
                line: $current->getLine(),
                frames: $frames,
                frameCounts: $this->frameCounts($frames),
            );

            $current = $current->getPrevious();
        }

        return $previous;
    }

    /**
     * @return list<DebugStackFrame>
     */
    private function frames(Throwable $throwable): array
    {
        $rawFrames = array_merge([
            [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'class' => null,
                'type' => null,
                'function' => 'throw',
            ],
        ], $throwable->getTrace());

        $frames = [];

        foreach ($rawFrames as $index => $rawFrame) {
            $resolved = $this->resolveFrameSource($rawFrame);
            $file = $this->stringValue($resolved['file'] ?? null);
            $line = $this->intValue($resolved['line'] ?? null);

            $frames[] = new DebugStackFrame(
                index: count($frames),
                label: $this->label($rawFrame),
                file: $file,
                line: $line,
                source: $this->sourceType($file, $rawFrame),
                sourceLines: $this->source->read($file, $line),
            );
        }

        return $frames;
    }

    /**
     * @param array<string, mixed> $frame
     * @return array<string, mixed>
     */
    private function resolveFrameSource(array $frame): array
    {
        $file = $this->stringValue($frame['file'] ?? null);

        if ($file !== null && $file !== '' && $file !== '[internal]') {
            return $frame;
        }

        $class = $this->stringValue($frame['class'] ?? null);
        $function = $this->stringValue($frame['function'] ?? null);

        if ($class === null || $function === null || !class_exists($class) || !method_exists($class, $function)) {
            return $frame;
        }

        try {
            $reflection = new ReflectionMethod($class, $function);
        } catch (ReflectionException) {
            return $frame;
        }

        $methodFile = $reflection->getFileName();

        if (!is_string($methodFile)) {
            return $frame;
        }

        $frame['file'] = $methodFile;
        $frame['line'] = $reflection->getStartLine();

        return $frame;
    }

    /**
     * @param array<string, mixed> $frame
     */
    private function label(array $frame): string
    {
        $class = $this->stringValue($frame['class'] ?? null);
        $type = $this->stringValue($frame['type'] ?? null) ?? '';
        $function = $this->stringValue($frame['function'] ?? null);

        if ($class !== null && $function !== null) {
            return $class . $type . $function . '()';
        }

        if ($function !== null) {
            return $function . '()';
        }

        return 'unknown frame';
    }

    /**
     * @param array<string, mixed> $frame
     * @return 'app'|'lpwork'|'vendor'|'other'
     */
    private function sourceType(?string $file, array $frame): string
    {
        $class = $this->stringValue($frame['class'] ?? null);

        if ($class !== null) {
            if (str_starts_with($class, 'App\\')) {
                return 'app';
            }

            if (str_starts_with($class, 'LPWork\\')) {
                return 'lpwork';
            }
        }

        if ($file === null || $file === '') {
            return 'other';
        }

        $basePath = str_replace('\\', '/', rtrim($this->applicationPath, '/'));
        $path = str_replace('\\', '/', $file);
        $relative = str_starts_with($path, $basePath . '/')
            ? substr($path, strlen($basePath . '/'))
            : $path;

        return match (true) {
            str_starts_with($relative, 'App/') => 'app',
            str_starts_with($relative, 'LPWork/') => 'lpwork',
            str_starts_with($relative, 'vendor/') => 'vendor',
            default => 'other',
        };
    }

    /**
     * @param list<DebugStackFrame> $frames
     * @return array{app: int, lpwork: int, vendor: int, other: int, all: int}
     */
    private function frameCounts(array $frames): array
    {
        $counts = [
            'app' => 0,
            'lpwork' => 0,
            'vendor' => 0,
            'other' => 0,
            'all' => count($frames),
        ];

        foreach ($frames as $frame) {
            match ($frame->source) {
                'app' => $counts['app']++,
                'lpwork' => $counts['lpwork']++,
                'vendor' => $counts['vendor']++,
                default => $counts['other']++,
            };
        }

        return $counts;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function intValue(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }
}
