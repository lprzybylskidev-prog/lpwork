<?php
declare(strict_types=1);

namespace LPwork\Logging;

use LPwork\Logging\Exception\LoggingConfigurationException;

/**
 * Typed configuration holder for application logging.
 */
final class LogConfiguration
{
    /**
     * @var string
     */
    private string $defaultChannel;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $channels;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->defaultChannel =
            (string) ($config["default_channel"] ?? "stderr");
        $this->channels = (array) ($config["channels"] ?? []);
    }

    /**
     * Returns the name of the default logging channel.
     *
     * @return string
     */
    public function defaultChannel(): string
    {
        return $this->defaultChannel;
    }

    /**
     * Returns configuration for the given channel.
     *
     * @param string $channel
     *
     * @return array<string, mixed>
     */
    public function channel(string $channel): array
    {
        if (!isset($this->channels[$channel])) {
            throw new LoggingConfigurationException(
                \sprintf('Logging channel "%s" is not defined.', $channel),
            );
        }

        return (array) $this->channels[$channel];
    }
}
