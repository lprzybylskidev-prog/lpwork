<?php

declare(strict_types=1);

namespace Tests\support\throttle;

use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottlePolicy;

final class ThrottleConfigBuilder
{
    public static function config(
        bool $web = false,
        bool $api = false,
        bool $cli = false,
        int $maxAttempts = 2,
        int $decaySeconds = 60,
    ): ThrottleConfig {
        return new ThrottleConfig('memory', [
            'http_web' => new ThrottlePolicy('http_web', $web, $maxAttempts, $decaySeconds),
            'http_api' => new ThrottlePolicy('http_api', $api, $maxAttempts, $decaySeconds),
            'cli' => new ThrottlePolicy('cli', $cli, $maxAttempts, $decaySeconds),
        ]);
    }
}
