<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Writer;

use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\ErrorLogEntry;
use LPwork\ErrorLog\Exception\ErrorLogWriteException;

/**
 * Persists error log entries in a database table.
 */
class DatabaseErrorLogWriter implements ErrorLogWriterInterface
{
    /**
     * @var DatabaseConnectionManagerInterface
     */
    private DatabaseConnectionManagerInterface $connections;

    /**
     * @var string
     */
    private string $connectionName;

    /**
     * @var string
     */
    private string $table;

    /**
     * @param DatabaseConnectionManagerInterface $connections
     * @param string                             $connectionName
     * @param string                             $table
     */
    public function __construct(
        DatabaseConnectionManagerInterface $connections,
        string $connectionName,
        string $table,
    ) {
        $this->connections = $connections;
        $this->connectionName = $connectionName;
        $this->table = $table;
    }

    /**
     * @inheritDoc
     */
    public function write(ErrorLogEntry $entry): void
    {
        $connection = $this->connections->get($this->connectionName)->connection();

        $payload = $entry->toArray();
        $contextJson = \json_encode($payload['context']);

        if ($contextJson === false) {
            throw new ErrorLogWriteException('Failed to encode error context.');
        }

        try {
            $connection->insert($this->table, [
                'id' => $entry->id(),
                'level' => $entry->level(),
                'code' => $entry->code(),
                'message' => $entry->message(),
                'exception_class' => $entry->exceptionClass(),
                'file' => $entry->file(),
                'line' => $entry->line(),
                'trace' => $entry->trace(),
                'context' => $contextJson,
                'created_at' => $entry->timestamp()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $throwable) {
            throw new ErrorLogWriteException(
                'Failed to insert error log entry into database.',
                0,
                $throwable,
            );
        }
    }
}
