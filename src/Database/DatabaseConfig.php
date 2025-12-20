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
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function toConnectionParams(): array
    {
        $driver = $this->config["driver"] ?? "pdo_mysql";
        $params = [
            "driver" => $driver,
            "host" => $this->config["host"] ?? "127.0.0.1",
            "port" => $this->config["port"] ?? null,
            "dbname" => $this->config["database"] ?? null,
            "user" => $this->config["username"] ?? null,
            "password" => $this->config["password"] ?? null,
            "charset" => $this->config["charset"] ?? "utf8mb4",
            "url" => $this->config["url"] ?? null,
        ];

        if ($driver === "pdo_sqlite") {
            $params["path"] = $this->config["path"] ?? null;
            unset($params["host"], $params["port"]);
        }

        return $params;
    }
}
