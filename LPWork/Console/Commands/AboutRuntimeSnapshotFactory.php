<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use function implode;

use LPWork\Config\Config;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Foundation\Application;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Translation\Translator;

/**
 * Creates about runtime snapshot factory instances from framework configuration.
 */
final readonly class AboutRuntimeSnapshotFactory
{
    /**
     * Creates a new value for this component.
     */
    public function create(
        Application $app,
        RuntimeEnvironment $environment,
        FrameworkMetadata $metadata,
        Translator $translator,
    ): AboutRuntimeSnapshot {
        return new AboutRuntimeSnapshot(
            name: $this->configString('app.name', 'LPWork'),
            frameworkVersion: $metadata->version(),
            basePath: $app->basePath(),
            phpVersion: PHP_VERSION,
            phpSapi: PHP_SAPI,
            operatingSystem: PHP_OS_FAMILY,
            timezone: date_default_timezone_get(),
            environment: $environment->name(),
            production: $environment->isProduction(),
            debug: $this->appDebug(),
            locale: $translator->locale(),
            memoryLimit: $this->iniValue('memory_limit'),
            cacheDriver: $this->configString('cache.default', 'unknown'),
            sessionDriver: $this->configString('session.default', 'unknown'),
            queueConnection: $this->configString('queue.default', 'unknown'),
            databaseConnection: $this->configString('database.default', 'unknown'),
            mailTransport: $this->configString('mail.default', 'unknown'),
            storageDisk: $this->configString('storage.default', 'unknown'),
            lockDriver: $this->configString('locks.driver', 'unknown'),
            lockStore: $this->configString('locks.store', 'unknown'),
            throttleStorage: $this->configString('throttle.storage', 'unknown'),
            broadcastingConnection: $this->configString('broadcasting.default', 'unknown'),
            notificationDatabase: $this->notificationDatabase(),
            schedulerHistory: $this->enabledState($this->configBool('schedule.history.enabled', false)),
            observabilityReporters: $this->observabilityReporters(),
            maintenanceStore: $this->configString('maintenance.file', 'unknown'),
            securityProfile: $this->configString('security.environment', $environment->name()),
            securityHeaders: $this->enabledState($this->configBool('security.profiles.' . $environment->name() . '.send_security_headers', false)),
            csrfProtection: $this->enabledState($this->configBool('security.profiles.' . $environment->name() . '.csrf.enabled', false)),
            loadedExtensions: count(get_loaded_extensions()),
        );
    }

    private function appDebug(): bool
    {
        try {
            return Config::getBool('app.debug');
        } catch (MissingVariableException) {
            return false;
        }
    }

    private function configString(string $key, string $fallback): string
    {
        try {
            return Config::getString($key);
        } catch (MissingVariableException) {
            return $fallback;
        }
    }

    private function configBool(string $key, bool $fallback): bool
    {
        try {
            return Config::getBool($key);
        } catch (MissingVariableException) {
            return $fallback;
        }
    }

    private function notificationDatabase(): string
    {
        return $this->configString('notifications.database.connection', 'unknown')
            . ':' . $this->configString('notifications.database.table', 'unknown');
    }

    private function observabilityReporters(): string
    {
        try {
            $reporters = Config::getArray('observability.metrics.enabled_reporters');
        } catch (MissingVariableException) {
            return 'unknown';
        }

        $names = array_values(array_filter($reporters, static fn(mixed $reporter): bool => is_string($reporter) && $reporter !== ''));

        if ($names === []) {
            return 'none';
        }

        return implode(', ', $names);
    }

    private function enabledState(bool $enabled): string
    {
        return $enabled ? 'enabled' : 'disabled';
    }

    private function iniValue(string $key): string
    {
        $value = ini_get($key);

        if ($value === false || $value === '') {
            return 'unknown';
        }

        return $value;
    }
}
