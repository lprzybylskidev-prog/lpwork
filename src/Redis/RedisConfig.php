<?php
declare(strict_types=1);

namespace LPwork\Redis;

/**
 * Value object holding Redis connection configuration.
 */
class RedisConfig
{
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
        $this->scheme = (string) ($config['scheme'] ?? 'tcp');
        $this->host = (string) ($config['host'] ?? '127.0.0.1');
        $this->port = (int) ($config['port'] ?? 6379);
        $this->database = (int) ($config['database'] ?? 0);
        $this->username = $config['username'] !== '' ? $config['username'] ?? null : null;
        $this->password = $config['password'] !== '' ? $config['password'] ?? null : null;
        $this->prefix = $config['prefix'] !== '' ? $config['prefix'] ?? null : null;
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
