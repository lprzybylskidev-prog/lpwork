<?php
declare(strict_types=1);

namespace LPwork\Exception;

/**
 * Wraps PHP native errors as exceptions.
 */
class PhpErrorException extends \ErrorException
{
    /**
     * @param string     $message
     * @param int        $severity
     * @param int        $code
     * @param string          $file
     * @param int             $line
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        int $severity,
        int $code,
        string $file,
        int $line,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $severity, $file, $line, $previous);
    }
}
