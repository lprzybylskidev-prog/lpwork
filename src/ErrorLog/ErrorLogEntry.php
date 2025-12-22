<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use Carbon\CarbonImmutable;

/**
 * Represents a structured error log entry.
 */
final class ErrorLogEntry
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $level;

    /**
     * @var string
     */
    private string $message;

    /**
     * @var int
     */
    private int $code;

    /**
     * @var string
     */
    private string $exceptionClass;

    /**
     * @var string
     */
    private string $file;

    /**
     * @var int
     */
    private int $line;

    /**
     * @var string
     */
    private string $trace;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * @var CarbonImmutable
     */
    private CarbonImmutable $timestamp;

    /**
     * @param string                 $id
     * @param string                 $level
     * @param string                 $message
     * @param int                    $code
     * @param string                 $exceptionClass
     * @param string                 $file
     * @param int                    $line
     * @param string                 $trace
     * @param array<string, mixed>   $context
     * @param CarbonImmutable        $timestamp
     */
    public function __construct(
        string $id,
        string $level,
        string $message,
        int $code,
        string $exceptionClass,
        string $file,
        int $line,
        string $trace,
        array $context,
        CarbonImmutable $timestamp,
    ) {
        $this->id = $id;
        $this->level = $level;
        $this->message = $message;
        $this->code = $code;
        $this->exceptionClass = $exceptionClass;
        $this->file = $file;
        $this->line = $line;
        $this->trace = $trace;
        $this->context = $context;
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function level(): string
    {
        return $this->level;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function exceptionClass(): string
    {
        return $this->exceptionClass;
    }

    /**
     * @return string
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function line(): int
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function trace(): string
    {
        return $this->trace;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return CarbonImmutable
     */
    public function timestamp(): CarbonImmutable
    {
        return $this->timestamp;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'message' => $this->message,
            'code' => $this->code,
            'exception_class' => $this->exceptionClass,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->trace,
            'context' => $this->context,
            'timestamp' => $this->timestamp->format(\DATE_ATOM),
        ];
    }
}
