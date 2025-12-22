<?php
declare(strict_types=1);

namespace LPwork\Http\Error;

use Carbon\CarbonImmutable;

/**
 * Immutable error context carrying structured diagnostic data.
 */
final class ErrorContext
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var int
     */
    private int $status;

    /**
     * @var string|null
     */
    private ?string $message;

    /**
     * @var string|null
     */
    private ?string $exceptionClass;

    /**
     * @var int|null
     */
    private ?int $code;

    /**
     * @var string|null
     */
    private ?string $file;

    /**
     * @var int|null
     */
    private ?int $line;

    /**
     * @var string|null
     */
    private ?string $trace;

    /**
     * @var array<string, mixed>
     */
    private array $request;

    /**
     * @var array<string, mixed>
     */
    private array $session;

    /**
     * @var array<string, mixed>
     */
    private array $env;

    /**
     * @var array<string, mixed>
     */
    private array $app;

    /**
     * @var CarbonImmutable
     */
    private CarbonImmutable $timestamp;

    /**
     * @param string                 $id
     * @param int                    $status
     * @param string|null            $message
     * @param string|null            $exceptionClass
     * @param int|null               $code
     * @param string|null            $file
     * @param int|null               $line
     * @param string|null            $trace
     * @param array<string, mixed>   $request
     * @param array<string, mixed>   $session
     * @param array<string, mixed>   $env
     * @param array<string, mixed>   $app
     * @param CarbonImmutable        $timestamp
     */
    public function __construct(
        string $id,
        int $status,
        ?string $message,
        ?string $exceptionClass,
        ?int $code,
        ?string $file,
        ?int $line,
        ?string $trace,
        array $request,
        array $session,
        array $env,
        array $app,
        CarbonImmutable $timestamp,
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->message = $message;
        $this->exceptionClass = $exceptionClass;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
        $this->trace = $trace;
        $this->request = $request;
        $this->session = $session;
        $this->env = $env;
        $this->app = $app;
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
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * @return string|null
     */
    public function exceptionClass(): ?string
    {
        return $this->exceptionClass;
    }

    /**
     * @return int|null
     */
    public function code(): ?int
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function file(): ?string
    {
        return $this->file;
    }

    /**
     * @return int|null
     */
    public function line(): ?int
    {
        return $this->line;
    }

    /**
     * @return string|null
     */
    public function trace(): ?string
    {
        return $this->trace;
    }

    /**
     * @return array<string, mixed>
     */
    public function request(): array
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function session(): array
    {
        return $this->session;
    }

    /**
     * @return array<string, mixed>
     */
    public function env(): array
    {
        return $this->env;
    }

    /**
     * @return array<string, mixed>
     */
    public function app(): array
    {
        return $this->app;
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
            'status' => $this->status,
            'message' => $this->message,
            'exception_class' => $this->exceptionClass,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->trace,
            'request' => $this->request,
            'session' => $this->session,
            'env' => $this->env,
            'app' => $this->app,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
