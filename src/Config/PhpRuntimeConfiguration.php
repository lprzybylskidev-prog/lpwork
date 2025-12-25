<?php
declare(strict_types=1);

namespace LPwork\Config;

use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration for PHP runtime settings.
 */
final class PhpRuntimeConfiguration
{
    use ConfigNormalizer;

    /**
     * @var string
     */
    private string $errorLog;

    /**
     * @var string
     */
    private string $errorReporting;

    /**
     * @var string
     */
    private string $memoryLimit;

    /**
     * @var int
     */
    private int $maxExecutionTime;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->errorLog = $this->stringVal($config['error_log'] ?? null, 'php.error_log', '', true);
        $this->errorReporting = $this->stringVal(
            $config['error_reporting'] ?? null,
            'php.error_reporting',
            'E_ALL',
            false,
        );
        $this->memoryLimit = $this->stringVal(
            $config['memory_limit'] ?? null,
            'php.memory_limit',
            '-1',
            false,
        );
        $this->maxExecutionTime = $this->intVal(
            $config['max_execution_time'] ?? null,
            'php.max_execution_time',
            0,
            0,
        );
    }

    public function errorLog(): string
    {
        return $this->errorLog;
    }

    public function errorReporting(): string
    {
        return $this->errorReporting;
    }

    public function memoryLimit(): string
    {
        return $this->memoryLimit;
    }

    public function maxExecutionTime(): int
    {
        return $this->maxExecutionTime;
    }
}
