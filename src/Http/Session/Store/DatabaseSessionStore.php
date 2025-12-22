<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Store;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionConfigurationException;
use LPwork\Http\Session\Exception\SessionStorageException;
use LPwork\Http\Session\SessionCookieParameters;
use LPwork\Http\Session\SessionState;
use Psr\Clock\ClockInterface;

/**
 * Database-backed session storage using default connection.
 */
class DatabaseSessionStore implements SessionStoreInterface
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
     * @var SessionIdGeneratorInterface
     */
    private SessionIdGeneratorInterface $idGenerator;

    /**
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @param DatabaseConnectionManager   $connections
     * @param string                      $connectionName
     * @param string                      $table
     * @param SessionIdGeneratorInterface $idGenerator
     * @param ClockInterface              $clock
     */
    public function __construct(
        DatabaseConnectionManager $connections,
        string $connectionName,
        string $table,
        SessionIdGeneratorInterface $idGenerator,
        ClockInterface $clock,
    ) {
        $this->connections = $connections;
        $this->connectionName = $connectionName;
        $this->table = $table;
        $this->idGenerator = $idGenerator;
        $this->clock = $clock;
    }

    /**
     * @inheritDoc
     */
    public function start(
        ?string $id,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): SessionState {
        $this->assertDefaultConnection();

        $sessionId = $id ?: $this->idGenerator->generate();
        $record = $this->fetch($sessionId);
        $now = $this->now();
        $nowTimestamp = $now->getTimestamp();

        if ($record === null) {
            return new SessionState($sessionId, [], $nowTimestamp);
        }

        $expiresAt = (int) $record['expires_at'];

        if ($expiresAt > 0 && $expiresAt < $nowTimestamp) {
            $this->destroy($sessionId);

            return new SessionState($sessionId, [], $nowTimestamp);
        }

        $payload = \json_decode((string) $record['payload'], true);

        if (!\is_array($payload)) {
            $this->destroy($sessionId);

            return new SessionState($sessionId, [], $nowTimestamp);
        }

        $data = (array) ($payload['data'] ?? []);
        $lastActivity = (int) ($payload['last_activity'] ?? $nowTimestamp);

        return new SessionState($sessionId, $data, $lastActivity);
    }

    /**
     * @inheritDoc
     */
    public function persist(
        SessionState $state,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): void {
        $this->assertDefaultConnection();

        $payload = \json_encode([
            'data' => $state->all(),
            'last_activity' => $state->lastActivity(),
        ]);

        if ($payload === false) {
            throw new SessionStorageException('Failed to encode session payload for database.');
        }

        $connection = $this->connection();
        $expiresAt = $this->now()->addSeconds($lifetime)->getTimestamp();

        $connection->delete($this->table, ['id' => $state->id()]);
        $connection->insert($this->table, [
            'id' => $state->id(),
            'payload' => $payload,
            'last_activity' => $state->lastActivity(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function destroy(string $id): void
    {
        $this->connection()->delete($this->table, ['id' => $id]);
    }

    /**
     * @inheritDoc
     */
    public function usesNativeCookie(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function cleanupExpired(int $lifetime): void
    {
        $this->assertDefaultConnection();
        $connection = $this->connection();
        $now = $this->now()->getTimestamp();

        $connection
            ->createQueryBuilder()
            ->delete($this->table)
            ->where('expires_at < :now')
            ->setParameter('now', $now)
            ->executeStatement();
    }

    /**
     * @param string $id
     *
     * @return array<string, mixed>|null
     */
    private function fetch(string $id): ?array
    {
        $connection = $this->connection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->select('id', 'payload', 'last_activity', 'expires_at')
            ->from($this->table)
            ->where('id = :id')
            ->setParameter('id', $id);

        /** @var array<string, mixed>|false $row */
        $row = $queryBuilder->executeQuery()->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * @return Connection
     */
    private function connection(): Connection
    {
        return $this->connections->get($this->connectionName)->connection();
    }

    /**
     * @return void
     */
    private function assertDefaultConnection(): void
    {
        $default = $this->connections->getDefaultConnectionName();

        if ($this->connectionName !== $default) {
            throw new SessionConfigurationException(
                \sprintf(
                    'Session database connection must be default ("%s"), "%s" given.',
                    $default,
                    $this->connectionName,
                ),
            );
        }
    }

    /**
     * @return CarbonImmutable
     */
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->clock->now());
    }
}
