<?php
declare(strict_types=1);

namespace LPwork\Redis;

use LPwork\Config\Support\ConfigNormalizer;

/**
 * Value object holding Redis connection configuration.
 */
class RedisConfig
{
    use ConfigNormalizer;

    /**
     * @var string
     */
    private string $scheme;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var int
     */
    private int $database;

    /**
     * @var string|null
     */
    private ?string $username;

    /**
     * @var string|null
     */
    private ?string $password;

    /**
     * @var string|null
     */
    private ?string $prefix;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->scheme = $this->stringVal($config['scheme'] ?? null, 'redis.scheme', 'tcp', false);
        $this->host = $this->stringVal($config['host'] ?? null, 'redis.host', '127.0.0.1', false);
        $this->port = $this->intVal($config['port'] ?? null, 'redis.port', 6379, 1);
        $this->database = $this->intVal($config['database'] ?? null, 'redis.database', 0, 0);
        $this->username =
            $config['username'] !== ''
                ? $this->stringVal($config['username'] ?? null, 'redis.username', null, true)
                : null;
        $this->password =
            $config['password'] !== ''
                ? $this->stringVal($config['password'] ?? null, 'redis.password', null, true)
                : null;
        $this->prefix =
            $config['prefix'] !== ''
                ? $this->stringVal($config['prefix'] ?? null, 'redis.prefix', null, true)
                : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return [
            'scheme' => $this->scheme,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toOptions(): array
    {
        $options = [];

        if ($this->prefix !== null) {
            $options['prefix'] = $this->prefix;
        }

        return $options;
    }
}
