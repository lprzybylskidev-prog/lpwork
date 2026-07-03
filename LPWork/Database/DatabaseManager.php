<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Config\ArrayConfigReader;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\Contracts\QueryReporter;
use LPWork\Database\Exceptions\InvalidDatabaseConfigException;
use LPWork\Database\Exceptions\InvalidDatabaseConnectionException;
use LPWork\Database\Exceptions\MissingDatabaseConfigException;

/**
 * Resolves configured database connections and exposes non-secret connection metadata.
 */
final class DatabaseManager
{
    /**
     * @var array<string, Connection>
     */
    private array $connections = [];

    private ArrayConfigReader $reader;

    private PdoConnectionFactory $factory;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly string $basePath,
        private readonly QueryReporter $reporter = new NullQueryReporter(),
        ?PdoConnectionFactory $factory = null,
    ) {
        $this->reader = $this->reader($config);
        $this->factory = $factory ?? new PdoConnectionFactory($this->basePath);
    }

    public function default(): Connection
    {
        return $this->connection($this->defaultConnectionName());
    }

    /**
     * Returns the configured database connection name used when no connection is requested explicitly.
     */
    public function defaultConnectionName(): string
    {
        return $this->reader->string('default');
    }

    /**
     * Returns a named database connection, creating and caching it on first use.
     */
    public function connection(string $name): Connection
    {
        if (array_key_exists($name, $this->connections)) {
            return $this->connections[$name];
        }

        $connections = $this->reader->arrayMap('connections');

        if (!array_key_exists($name, $connections)) {
            throw new InvalidDatabaseConnectionException($name);
        }

        $this->connections[$name] = $this->factory->create($name, $connections[$name], $this->reporter);

        return $this->connections[$name];
    }

    /**
     * Returns all configured database connection names.
     *
     * @return list<string>
     */
    public function connectionNames(): array
    {
        return array_keys($this->reader->arrayMap('connections'));
    }

    /**
     * Returns the configured driver type for a named database connection.
     */
    public function connectionDriverName(string $name): string
    {
        $config = $this->connectionConfig($name);
        $driver = $config['driver'] ?? null;

        return is_string($driver) && $driver !== '' ? $driver : 'unknown';
    }

    /**
     * Returns a non-secret endpoint summary for a named database connection.
     */
    public function connectionEndpoint(string $name): ?string
    {
        $config = $this->connectionConfig($name);
        $driver = $this->connectionDriverName($name);

        if ($driver === 'sqlite') {
            $database = $config['database'] ?? null;

            return is_string($database) && $database !== '' ? $database : null;
        }

        $host = $config['host'] ?? null;
        $port = $config['port'] ?? null;
        $database = $config['database'] ?? null;

        if (!is_string($host) || $host === '' || !is_int($port)) {
            return null;
        }

        return sprintf(
            '%s:%d%s',
            $host,
            $port,
            is_string($database) && $database !== '' ? '/' . $database : '',
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function connectionConfig(string $name): array
    {
        $connections = $this->reader->arrayMap('connections');

        if (!array_key_exists($name, $connections)) {
            throw new InvalidDatabaseConnectionException($name);
        }

        return $connections[$name];
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingDatabaseConfigException => new MissingDatabaseConfigException($key),
            invalidException: static fn(string $key): InvalidDatabaseConfigException => new InvalidDatabaseConfigException($key),
        );
    }
}
