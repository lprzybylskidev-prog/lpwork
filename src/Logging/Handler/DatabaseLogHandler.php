<?php
declare(strict_types=1);

namespace LPwork\Logging\Handler;

use Doctrine\DBAL\Connection;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level as MonologLevel;
use Monolog\LogRecord;

/**
 * Persists log records into a database table using Doctrine DBAL connection.
 *
 * Expected table schema (example):
 * - channel (string)
 * - level (int)
 * - message (string)
 * - context (json/text)
 * - extra (json/text)
 * - created_at (int or datetime)
 */
class DatabaseLogHandler extends AbstractProcessingHandler
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var string
     */
    private string $table;

    /**
     * @param Connection   $connection
     * @param string       $table
     * @param MonologLevel $level
     * @param bool         $bubble
     */
    public function __construct(
        Connection $connection,
        string $table,
        MonologLevel $level,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * @return void
     */
    protected function write(\Monolog\LogRecord $record): void
    {
        $context = $this->jsonEncode($record->context);
        $extra = $this->jsonEncode($record->extra);
        $createdAt = $record->datetime->getTimestamp();

        $this->connection->insert($this->table, [
            'channel' => $record->channel,
            'level' => $record->level->value,
            'message' => $record->message,
            'context' => $context,
            'extra' => $extra,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     */
    private function jsonEncode(array $payload): string
    {
        $encoded = \json_encode($payload);

        if ($encoded === false) {
            return '{}';
        }

        return $encoded;
    }
}
