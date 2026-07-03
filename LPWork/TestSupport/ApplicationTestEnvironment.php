<?php

declare(strict_types=1);

namespace Tests\support;

use App\Shared\Configs\AppConfig;
use App\Shared\Configs\BroadcastingConfig;
use App\Shared\Configs\CacheConfig;
use App\Shared\Configs\DatabaseConfig;
use App\Shared\Configs\ErrorConfig;
use App\Shared\Configs\LockConfig;
use App\Shared\Configs\LoggingConfig;
use App\Shared\Configs\MailConfig;
use App\Shared\Configs\NotificationsConfig;
use App\Shared\Configs\QueueConfig;
use App\Shared\Configs\RoutingConfig;
use App\Shared\Configs\ScheduleConfig;
use App\Shared\Configs\SecurityConfig;
use App\Shared\Configs\SessionConfig;
use App\Shared\Configs\StorageConfig;
use App\Shared\Configs\ThrottleConfig;
use App\Shared\Configs\ViewConfig;
use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;
use RuntimeException;

final class ApplicationTestEnvironment
{
    /**
     * @var list<string>
     */
    private static array $directories = [];

    private function __construct(private readonly string $basePath) {}

    public static function create(): self
    {
        $projectRoot = \Tests\support\ProjectPaths::root();
        $basePath = sys_get_temp_dir() . '/lpwork_application_' . uniqid('', true);

        if (!mkdir($basePath)) {
            throw new RuntimeException('Could not create temporary application directory.');
        }

        self::$directories[] = $basePath;

        self::copyFile($projectRoot . '/.env', $basePath . '/.env');
        self::copyDirectory($projectRoot . '/App/Shared/Configs', $basePath . '/App/Shared/Configs');
        self::copyDirectory($projectRoot . '/App/Shared/lang', $basePath . '/App/Shared/lang');
        self::copyDirectory($projectRoot . '/App/Modules', $basePath . '/App/Modules');
        self::copyDirectoryIfExists($projectRoot . '/resources', $basePath . '/resources');

        return new self($basePath);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function envPath(): string
    {
        return $this->basePath . '/.env';
    }

    public function configPath(): string
    {
        return $this->basePath . '/App/Shared/Configs';
    }

    public function appendEnvLine(string $line): void
    {
        EnvironmentTestFiles::appendLine($line, $this->envPath());
    }

    public function appendEnvValue(string $key, string|int|float|bool $value): void
    {
        EnvironmentTestFiles::appendValue($key, $value, $this->envPath());
    }

    public function setEnvValue(string $key, string|int|float|bool $value): void
    {
        EnvironmentTestFiles::setValue($key, $value, $this->envPath());
    }

    public function appendConfigValue(string $fileName, string $key, mixed $value): string
    {
        $path = $this->basePath . '/storage/framework/cache/config.php';
        $config = is_file($path) ? $this->readCachedConfig($path) : $this->applicationConfig();
        $configKey = pathinfo($fileName, PATHINFO_FILENAME);

        if (!array_key_exists($configKey, $config)) {
            $config[$configKey] = [];
        }

        $this->setNestedValue($config[$configKey], explode('.', $key), $value);

        return $this->writeFile(
            'storage/framework/cache/config.php',
            "<?php\n\n"
                . "declare(strict_types=1);\n\n"
                . 'return ' . var_export($config, true) . ";\n",
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function createConfig(string $fileName, array $config = []): string
    {
        return ConfigTestFiles::createConfig($fileName, $config, $this->configPath());
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    private function applicationConfig(): array
    {
        Environment::reset();
        Config::reset();

        Environment::init($this->envPath());
        Config::initDefinitions($this->configDefinitions());

        $config = Config::all();

        Environment::reset();
        Config::reset();

        return $config;
    }

    /**
     * @return list<ConfigDefinition>
     */
    private function configDefinitions(): array
    {
        return [
            new AppConfig(),
            new BroadcastingConfig(),
            new CacheConfig(),
            new DatabaseConfig(),
            new ErrorConfig(),
            new LockConfig(),
            new LoggingConfig(),
            new MailConfig(),
            new NotificationsConfig(),
            new QueueConfig(),
            new RoutingConfig(),
            new ScheduleConfig(),
            new SessionConfig(),
            new SecurityConfig(),
            new StorageConfig(),
            new ThrottleConfig(),
            new ViewConfig(),
        ];
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    private function readCachedConfig(string $path): array
    {
        $config = include $path;

        if (!is_array($config)) {
            throw new RuntimeException(sprintf('Cached config file does not return an array: %s', $path));
        }

        $normalized = [];

        foreach ($config as $key => $value) {
            if (!is_string($key) || !is_array($value)) {
                throw new RuntimeException(sprintf('Cached config file has an invalid shape: %s', $path));
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<array-key, mixed> $config
     * @param list<string> $keys
     */
    private function setNestedValue(array &$config, array $keys, mixed $value): void
    {
        $key = array_shift($keys);

        if ($key === null || $key === '') {
            throw new RuntimeException('Config key cannot be empty.');
        }

        if ($keys === []) {
            $config[$key] = $value;

            return;
        }

        if (!array_key_exists($key, $config) || !is_array($config[$key])) {
            $config[$key] = [];
        }

        $this->setNestedValue($config[$key], $keys, $value);
    }

    public function writeFile(string $path, string $content): string
    {
        $path = $this->basePath . '/' . ltrim($path, '/');
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create directory: %s', $directory));
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException(sprintf('Could not write file: %s', $path));
        }

        return $path;
    }

    public static function removeDirectories(): void
    {
        foreach (self::$directories as $directory) {
            self::removeDirectory($directory);
        }

        self::$directories = [];
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            throw new RuntimeException(sprintf('Source directory does not exist: %s', $source));
        }

        if (!is_dir($destination) && !mkdir($destination, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create directory: %s', $destination));
        }

        $items = scandir($source);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read directory: %s', $source));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $item;
            $destinationPath = $destination . '/' . $item;

            if (is_dir($sourcePath)) {
                self::copyDirectory($sourcePath, $destinationPath);

                continue;
            }

            self::copyFile($sourcePath, $destinationPath);
        }
    }

    private static function copyDirectoryIfExists(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        self::copyDirectory($source, $destination);
    }

    private static function copyFile(string $source, string $destination): void
    {
        if (!is_file($source)) {
            throw new RuntimeException(sprintf('Source file does not exist: %s', $source));
        }

        $destinationDirectory = dirname($destination);

        if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create directory: %s', $destinationDirectory));
        }

        if (!copy($source, $destination)) {
            throw new RuntimeException(sprintf('Could not copy file from %s to %s.', $source, $destination));
        }
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read directory: %s', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                self::removeDirectory($path);

                continue;
            }

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
