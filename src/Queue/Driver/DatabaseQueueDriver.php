<?php
declare(strict_types=1);

namespace LPwork\Queue\Driver;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Queue\Contract\JobSerializerInterface;
use LPwork\Queue\Contract\QueueDriverInterface;
use LPwork\Queue\QueueJob;

/**
 * Database-backed queue driver (simple table-based FIFO).
 */
class DatabaseQueueDriver implements QueueDriverInterface
{
    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $connections;

    /**
     * @var string
     */
    private string $connectionName;

    /**
     * @var string
     */
    private string $table;

    /**
     * @var JobSerializerInterface
     */
    private JobSerializerInterface $serializer;

    /**
     * @param DatabaseConnectionManager $connections
     * @param string                    $connectionName
     * @param string                    $table
     * @param JobSerializerInterface    $serializer
     */
    public function __construct(
        DatabaseConnectionManager $connections,
        string $connectionName,
        string $table,
        JobSerializerInterface $serializer,
    ) {
        $this->connections = $connections;
        $this->connectionName = $connectionName;
        $this->table = $table;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function push(QueueJob $job): void
    {
        $connection = $this->connection();
        $payload = $this->serializer->serialize($job);
        $now = CarbonImmutable::now();

        $connection->insert($this->table, [
            'id' => $job->id(),
            'queue' => $job->queue(),
            'payload' => $payload,
            'attempts' => $job->attempts(),
            'available_at' => $job->availableAt()?->format('Y-m-d H:i:s'),
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function pop(int $timeoutSeconds): ?QueueJob
    {
        $connection = $this->connection();
        $deadline = \time() + $timeoutSeconds;

        while (true) {
            $connection->beginTransaction();

            try {
                $row = $connection
                    ->createQueryBuilder()
                    ->select('*')
                    ->from($this->table)
                    ->where('available_at IS NULL OR available_at <= :now')
                    ->orderBy('available_at', 'ASC')
                    ->addOrderBy('created_at', 'ASC')
                    ->setMaxResults(1)
                    ->setParameter('now', (new CarbonImmutable())->format('Y-m-d H:i:s'))
                    ->executeQuery()
                    ->fetchAssociative();

                if ($row === false) {
                    $connection->commit();

                    if ($timeoutSeconds === 0 || \time() >= $deadline) {
                        return null;
                    }

                    \usleep(200_000);
                    continue;
                }

                $connection->delete($this->table, ['id' => $row['id']]);
                $connection->commit();

                $job = $this->serializer->deserialize((string) $row['payload']);
                $attempts = (int) ($row['attempts'] ?? 0) + 1;

                return $job->withAttempts($attempts);
            } catch (\Throwable $throwable) {
                $connection->rollBack();
                throw $throwable;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function ack(QueueJob $job): void
    {
        // already removed on pop
    }

    /**
     * @inheritDoc
     */
    public function reject(QueueJob $job, bool $requeue): void
    {
        if ($requeue) {
            $this->push($job);
        }
    }

    /**
     * @inheritDoc
     */
    public function purge(): void
    {
        $connection = $this->connection();
        $connection->executeStatement(
            \sprintf('DELETE FROM %s', $connection->quoteIdentifier($this->table)),
        );
    }

    /**
     * @return Connection
     */
    private function connection(): Connection
    {
        return $this->connections->get($this->connectionName)->connection();
    }
}
