<?php
declare(strict_types=1);

namespace LPwork\Database;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use LPwork\Database\Exception\DatabaseTimezoneConfigurationException;
use LPwork\Time\TimezoneContext;

/**
 * Applies timezone settings to database connections.
 */
final class DatabaseTimezoneConfigurator
{
    /**
     * @var TimezoneContext
     */
    private TimezoneContext $timezone;

    /**
     * @param TimezoneContext $timezone
     */
    public function __construct(TimezoneContext $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Sets connection session timezone for supported drivers.
     *
     * @param Connection $connection
     * @param string     $driver
     * @param string|null $timezoneOverride
     *
     * @return void
     */
    public function configure(
        Connection $connection,
        string $driver,
        ?string $timezoneOverride = null,
    ): void {
        $timezoneContext = $this->resolveTimezone($timezoneOverride);

        if ($this->isSqlite($driver)) {
            return;
        }

        if ($this->isMysql($driver)) {
            $this->applyTimezone($connection, 'SET time_zone = ?', $timezoneContext->name());

            return;
        }

        if ($this->isPostgres($driver)) {
            $this->applyTimezone($connection, 'SET TIME ZONE ?', $timezoneContext->name());

            return;
        }

        if ($this->isSqlServer($driver)) {
            $offset = CarbonImmutable::now($timezoneContext->timezone())->format('P');
            $this->applyTimezone($connection, 'SET TIME ZONE ?', $offset);

            return;
        }

        throw new DatabaseTimezoneConfigurationException(
            \sprintf('Driver "%s" does not support timezone configuration.', $driver),
        );
    }

    /**
     * @param Connection $connection
     * @param string     $statement
     * @param string     $value
     *
     * @return void
     */
    private function applyTimezone(Connection $connection, string $statement, string $value): void
    {
        try {
            $connection->executeStatement($statement, [$value]);
        } catch (\Throwable $exception) {
            throw new DatabaseTimezoneConfigurationException(
                \sprintf('Failed to apply timezone "%s" to database connection.', $value),
                0,
                $exception,
            );
        }
    }

    /**
     * @param string $driver
     *
     * @return bool
     */
    private function isMysql(string $driver): bool
    {
        return \str_contains($driver, 'mysql');
    }

    /**
     * @param string $driver
     *
     * @return bool
     */
    private function isPostgres(string $driver): bool
    {
        return \str_contains($driver, 'pgsql');
    }

    /**
     * @param string $driver
     *
     * @return bool
     */
    private function isSqlite(string $driver): bool
    {
        return \str_contains($driver, 'sqlite');
    }

    /**
     * @param string $driver
     *
     * @return bool
     */
    private function isSqlServer(string $driver): bool
    {
        return \str_contains($driver, 'sqlsrv') || \str_contains($driver, 'mssql');
    }

    /**
     * @param string|null $timezoneOverride
     *
     * @return TimezoneContext
     */
    private function resolveTimezone(?string $timezoneOverride): TimezoneContext
    {
        if ($timezoneOverride === null || \trim($timezoneOverride) === '') {
            return $this->timezone;
        }

        return new TimezoneContext($timezoneOverride);
    }
}
