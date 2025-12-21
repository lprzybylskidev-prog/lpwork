<?php
declare(strict_types=1);

namespace LPwork\Database;

/**
 * Value object for database connection configuration.
 */
class DatabaseConfig
{
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
        $this->config = $config;
        $this->driver = (string) ($config["driver"] ?? "pdo_mysql");
    }

    /**
     * @return array<string, mixed>
     */
    public function toConnectionParams(): array
    {
        $params = [
            "driver" => $this->driver,
            "host" => $this->config["host"] ?? "127.0.0.1",
            "port" => $this->config["port"] ?? null,
            "dbname" => $this->config["database"] ?? null,
            "user" => $this->config["username"] ?? null,
            "password" => $this->config["password"] ?? null,
            "charset" => $this->config["charset"] ?? "utf8mb4",
            "url" => $this->config["url"] ?? null,
        ];

        if ($this->driver === "pdo_sqlite") {
            $params["path"] = $this->config["path"] ?? null;
            unset($params["host"], $params["port"]);
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
