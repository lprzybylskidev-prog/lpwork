<?php
declare(strict_types=1);

namespace LPwork\Database;

use LPwork\Config\Support\ConfigNormalizer;

/**
 * Value object for database connection configuration.
 */
class DatabaseConfig
{
    use ConfigNormalizer;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var string
     */
    private string $driver;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->driver = $this->stringVal(
            $config['driver'] ?? null,
            'database.driver',
            'pdo_mysql',
            false,
        );

        $host = $this->stringVal($config['host'] ?? null, 'database.host', '127.0.0.1', true);
        $port = $config['port'] ?? null;
        if ($port !== null) {
            $port = $this->intVal($port, 'database.port', null, 1);
        }

        $dbname = $this->stringVal($config['database'] ?? null, 'database.database', null, true);
        $user = $this->stringVal($config['username'] ?? null, 'database.username', null, true);
        $password = $config['password'] ?? null;
        if ($password !== null) {
            $password = $this->stringVal($password, 'database.password', '', true);
        }

        $charset = $this->stringVal(
            $config['charset'] ?? null,
            'database.charset',
            'utf8mb4',
            false,
        );
        $url = $config['url'] ?? null;
        if ($url !== null) {
            $url = $this->stringVal($url, 'database.url', null, false);
        }

        $this->config = [
            'host' => $host,
            'port' => $port,
            'database' => $dbname,
            'username' => $user,
            'password' => $password,
            'charset' => $charset,
            'url' => $url,
            'path' => $config['path'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toConnectionParams(): array
    {
        $params = [
            'driver' => $this->driver,
            'host' => $this->config['host'] ?? '127.0.0.1',
            'port' => $this->config['port'] ?? null,
            'dbname' => $this->config['database'] ?? null,
            'user' => $this->config['username'] ?? null,
            'password' => $this->config['password'] ?? null,
            'charset' => $this->config['charset'] ?? 'utf8mb4',
            'url' => $this->config['url'] ?? null,
        ];

        if ($this->driver === 'pdo_sqlite') {
            $path = $this->stringVal($this->config['path'] ?? null, 'database.path', null, false);
            $params['path'] = $path;
            unset($params['host'], $params['port']);
        }

        return $params;
    }

    /**
     * Returns configured Doctrine driver name.
     *
     * @return string
     */
    public function driver(): string
    {
        return $this->driver;
    }
}
