<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\Config\ArrayConfigReader;
use LPWork\Throttle\Exceptions\InvalidThrottleConfigException;
use LPWork\Throttle\Exceptions\MissingThrottleConfigException;

/**
 * Creates throttle config factory instances from framework configuration.
 */
final readonly class ThrottleConfigFactory
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config): ThrottleConfig
    {
        $reader = $this->reader($config);
        $policies = [];

        foreach ($reader->arrayMap('policies') as $name => $policyConfig) {
            $policies[$name] = $this->policy($name, $policyConfig);
        }

        return new ThrottleConfig(
            storage: $reader->string('storage'),
            policies: $policies,
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function policy(string $name, array $config): ThrottlePolicy
    {
        $reader = $this->reader($config);
        $maxAttempts = $reader->int('max_attempts', "policies.{$name}.max_attempts");
        $decaySeconds = $reader->int('decay_seconds', "policies.{$name}.decay_seconds");

        if ($maxAttempts < 1) {
            throw new InvalidThrottleConfigException("policies.{$name}.max_attempts");
        }

        if ($decaySeconds < 1) {
            throw new InvalidThrottleConfigException("policies.{$name}.decay_seconds");
        }

        return new ThrottlePolicy(
            name: $name,
            enabled: $reader->bool('enabled', "policies.{$name}.enabled"),
            maxAttempts: $maxAttempts,
            decaySeconds: $decaySeconds,
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingThrottleConfigException => new MissingThrottleConfigException($key),
            invalidException: static fn(string $key): InvalidThrottleConfigException => new InvalidThrottleConfigException($key),
        );
    }
}
