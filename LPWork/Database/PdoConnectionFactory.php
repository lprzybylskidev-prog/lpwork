<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Config\ArrayConfigReader;
use LPWork\Database\Contracts\QueryReporter;
use LPWork\Database\Exceptions\DatabaseConnectionException;
use LPWork\Database\Exceptions\InvalidDatabaseConfigException;
use LPWork\Database\Exceptions\MissingDatabaseConfigException;
use LPWork\Database\Exceptions\UnsupportedDatabaseDriverException;
use PDO;
use PDOException;

/**
 * Creates pdo connection factory instances from framework configuration.
 */
final readonly class PdoConnectionFactory
{
    /**
     * Creates a new PdoConnectionFactory instance.
     */
    public function __construct(
        private string $basePath,
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(string $name, array $config, QueryReporter $reporter): PdoConnection
    {
        $reader = $this->reader($config);
        $driver = $reader->string('driver', "connections.{$name}.driver");

        try {
            $pdo = new PDO(
                dsn: $this->dsn($name, $driver, $config),
                username: $this->username($driver, $config, $name),
                password: $this->password($driver, $config, $name),
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            );
        } catch (PDOException $exception) {
            throw DatabaseConnectionException::failed($name, $exception);
        }

        return new PdoConnection($name, $pdo, $reporter);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function dsn(string $name, string $driver, array $config): string
    {
        return match ($driver) {
            'mysql' => $this->mysqlDsn($name, $config),
            'pgsql' => $this->pgsqlDsn($name, $config),
            'sqlite' => $this->sqliteDsn($name, $config),
            default => throw new UnsupportedDatabaseDriverException($driver),
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function mysqlDsn(string $name, array $config): string
    {
        $reader = $this->reader($config);

        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $reader->string('host', "connections.{$name}.host"),
            $reader->int('port', "connections.{$name}.port"),
            $reader->string('database', "connections.{$name}.database"),
            $reader->string('charset', "connections.{$name}.charset"),
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function pgsqlDsn(string $name, array $config): string
    {
        $reader = $this->reader($config);

        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $reader->string('host', "connections.{$name}.host"),
            $reader->int('port', "connections.{$name}.port"),
            $reader->string('database', "connections.{$name}.database"),
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function sqliteDsn(string $name, array $config): string
    {
        $database = $this->reader($config)->string('database', "connections.{$name}.database");

        if ($database === ':memory:') {
            return 'sqlite::memory:';
        }

        return 'sqlite:' . $this->absolutePath($database);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function username(string $driver, array $config, string $name): ?string
    {
        if ($driver === 'sqlite') {
            return null;
        }

        return $this->reader($config)->optionalString('username', "connections.{$name}.username", allowEmpty: true);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function password(string $driver, array $config, string $name): ?string
    {
        if ($driver === 'sqlite') {
            return null;
        }

        return $this->reader($config)->optionalString('password', "connections.{$name}.password", allowEmpty: true);
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
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
