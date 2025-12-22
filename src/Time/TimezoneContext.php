<?php
declare(strict_types=1);

namespace LPwork\Time;

use LPwork\Time\Exception\TimezoneConfigurationException;

/**
 * Represents the configured application timezone and validates its value.
 */
final class TimezoneContext
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var \DateTimeZone
     */
    private \DateTimeZone $timezone;

    /**
     * @param string $timezone
     */
    public function __construct(string $timezone)
    {
        $normalized = \trim($timezone);

        if ($normalized === '') {
            $normalized = 'UTC';
        }

        try {
            $this->timezone = new \DateTimeZone($normalized);
        } catch (\Throwable $exception) {
            throw new TimezoneConfigurationException(
                \sprintf('Configured timezone "%s" is invalid.', $timezone),
                0,
                $exception,
            );
        }

        $this->name = $normalized;
    }

    /**
     * Returns the normalized timezone name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the timezone instance.
     *
     * @return \DateTimeZone
     */
    public function timezone(): \DateTimeZone
    {
        return $this->timezone;
    }
}
