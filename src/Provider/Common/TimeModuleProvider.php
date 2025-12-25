<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use Carbon\CarbonImmutable;
use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Time\CarbonClock;
use LPwork\Time\TimezoneContext;
use Psr\Clock\ClockInterface;

/**
 * Registers time-related services (timezone and clock).
 */
final class TimeModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            TimezoneContext::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): TimezoneContext {
                $timezone = $config->getString('app.timezone', 'UTC');

                return new TimezoneContext($timezone);
            }),
            \DateTimeZone::class => \DI\factory(static function (
                TimezoneContext $timezoneContext,
            ): \DateTimeZone {
                return $timezoneContext->timezone();
            }),
            ClockInterface::class => \DI\autowire(CarbonClock::class),
            CarbonImmutable::class => \DI\factory(static function (
                TimezoneContext $timezoneContext,
            ): CarbonImmutable {
                return CarbonImmutable::now($timezoneContext->timezone());
            }),
        ]);
    }
}
