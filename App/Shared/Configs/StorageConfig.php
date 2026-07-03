<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;
use LPWork\Shared\Exceptions\SingletonInstanceException;

/**
 * Configures storage disks for local files, public files, memory, S3, FTP, and SFTP.
 */
final class StorageConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'storage';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $default = $this->env('STORAGE_DISK', 'local');
        $disks = [
            'local' => $this->disk('local'),
            'public' => $this->disk('public'),
        ];

        $disks[$default] = $this->disk($default);

        return [
            // STORAGE_DISK selects the default disk; local and public are always declared.
            'default' => $default,
            'disks' => $disks,
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function disk(string $disk): array
    {
        return match ($disk) {
            // Memory disks are process-local and useful for tests or temporary flows.
            'memory' => [
                'driver' => 'memory',
            ],
            // S3 supports AWS S3 and compatible endpoints such as MinIO.
            's3' => [
                'driver' => 's3',
                'bucket' => $this->env('STORAGE_S3_BUCKET', 'lpwork'),
                'region' => $this->env('STORAGE_S3_REGION', 'us-east-1'),
                'access_key' => $this->env('STORAGE_S3_ACCESS_KEY', ''),
                'secret_key' => $this->env('STORAGE_S3_SECRET_KEY', ''),
                'endpoint' => $this->env('STORAGE_S3_ENDPOINT', 'http://127.0.0.1:9000'),
                'path_style' => filter_var($this->env('STORAGE_S3_PATH_STYLE', 'true'), FILTER_VALIDATE_BOOL),
            ],
            // FTP requires a reachable FTP server and credentials.
            'ftp' => [
                'driver' => 'ftp',
                'host' => $this->env('STORAGE_FTP_HOST', '127.0.0.1'),
                'username' => $this->env('STORAGE_FTP_USERNAME', ''),
                'password' => $this->env('STORAGE_FTP_PASSWORD', ''),
                'root' => $this->env('STORAGE_FTP_ROOT', ''),
                'port' => (int) $this->env('STORAGE_FTP_PORT', '21'),
                'timeout_seconds' => (int) $this->env('STORAGE_FTP_TIMEOUT_SECONDS', '30'),
                'ssl' => filter_var($this->env('STORAGE_FTP_SSL', 'false'), FILTER_VALIDATE_BOOL),
                'passive' => filter_var($this->env('STORAGE_FTP_PASSIVE', 'true'), FILTER_VALIDATE_BOOL),
            ],
            // SFTP requires a reachable SSH/SFTP server and credentials.
            'sftp' => [
                'driver' => 'sftp',
                'host' => $this->env('STORAGE_SFTP_HOST', '127.0.0.1'),
                'username' => $this->env('STORAGE_SFTP_USERNAME', ''),
                'password' => $this->env('STORAGE_SFTP_PASSWORD', ''),
                'root' => $this->env('STORAGE_SFTP_ROOT', ''),
                'port' => (int) $this->env('STORAGE_SFTP_PORT', '22'),
                'timeout_seconds' => (int) $this->env('STORAGE_SFTP_TIMEOUT_SECONDS', '30'),
            ],
            // Public files are served from public/storage through the /storage URL prefix.
            'public' => [
                'driver' => 'local',
                'root' => 'public/storage',
                'url' => '/storage',
            ],
            // Local private files live under the application's storage directory.
            default => [
                'driver' => 'local',
                'root' => 'storage',
            ],
        };
    }

    private function env(string $key, string $default): string
    {
        try {
            return Environment::get($key, $default);
        } catch (SingletonInstanceException) {
            return $default;
        }
    }
}
