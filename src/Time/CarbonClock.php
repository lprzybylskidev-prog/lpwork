<?php
declare(strict_types=1);

namespace LPwork\Time;

use Carbon\CarbonImmutable;
use LPwork\Time\Exception\TimezoneConfigurationException;
use Psr\Clock\ClockInterface;

/**
 * PSR-20 clock backed by CarbonImmutable with configured timezone.
 */
final class CarbonClock implements ClockInterface
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
     * Returns current time as CarbonImmutable in the configured timezone.
     *
     * @return \DateTimeImmutable
     */
    public function now(): \DateTimeImmutable
    {
        try {
            return CarbonImmutable::now($this->timezone->timezone());
        } catch (\Throwable $exception) {
            throw new TimezoneConfigurationException(
                'Failed to create CarbonImmutable for the configured timezone.',
                0,
                $exception,
            );
        }
    }
}
