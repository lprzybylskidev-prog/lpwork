<?php

declare(strict_types=1);

namespace Tests\support\testing;

use Closure;
use LPWork\Bootstrap\Bootstrap;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\DebugDump\Debug;
use LPWork\Environment\Environment;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Url\Url;
use Tests\support\exceptions\TestSupportException;
use Tests\support\testing\Container\TestContainer;

final class ApplicationTestHarness
{
    /**
     * @var list<string>
     */
    private static array $directories = [];

    private ?Application $application = null;

    /**
     * @var list<Closure(Application): void>
     */
    private array $afterBootstrap = [];

    private function __construct(
        private readonly string $basePath,
    ) {}

    public static function create(): self
    {
        $basePath = self::createTemporaryDirectory('lpwork_app_harness_');

        foreach (['App/Shared/Configs', 'App/Shared/lang', 'App/Modules', 'storage/cache', 'storage/framework/cache', 'storage/logs'] as $directory) {
            self::ensureDirectory($basePath . '/' . $directory);
        }

        return new self($basePath);
    }

    public static function fromProjectDefaults(): self
    {
        $harness = self::create();
        $harness->copyFromProject('.env');
        $harness->copyFromProject('App/Shared/Configs');
        $harness->copyFromProject('App/Shared/lang');
        $harness->copyFromProject('App/Modules');
        $harness->copyFromProject('.devcontainer');
        $harness->copyFromProjectIfExists('resources');

        return $harness;
    }

    public static function resetFrameworkState(): void
    {
        Debug::reset();
        Url::reset();
        Environment::reset();
        Config::reset();
    }

    public static function removeDirectories(): void
    {
        foreach (self::$directories as $directory) {
            self::removeDirectory($directory);
        }

        self::$directories = [];
    }

    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    public function envPath(): string
    {
        return $this->basePath('.env');
    }

    public function configPath(string $fileName = ''): string
    {
        return $this->basePath('storage/framework/cache' . ($fileName === '' ? '' : '/' . ltrim($fileName, '/')));
    }

    /**
     * @param array<string, string|int|float|bool> $values
     */
    public function writeEnv(array $values): self
    {
        $lines = [];

        foreach ($values as $key => $value) {
            $lines[] = $key . '=' . $this->envValue($value);
        }

        return $this->writeFile('.env', implode("\n", $lines) . "\n");
    }

    public function setEnvValue(string $key, string|int|float|bool $value): self
    {
        $values = $this->readEnvValues();
        $values[$key] = $value;

        return $this->writeEnv($values);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function writeConfig(string $fileName, array $config): self
    {
        return $this->writeFile(
            'storage/framework/cache/' . $fileName,
            "<?php\n\n"
                . "declare(strict_types=1);\n\n"
                . 'return ' . var_export($config, true) . ";\n",
        );
    }

    public function writeFile(string $path, string $content): self
    {
        $absolutePath = $this->basePath($path);
        self::ensureDirectory(dirname($absolutePath));

        if (file_put_contents($absolutePath, $content) === false) {
            throw TestSupportException::testFileCouldNotBeWritten($absolutePath);
        }

        return $this;
    }

    public function copyFromProject(string $path): self
    {
        $projectRoot = \Tests\support\ProjectPaths::root();
        $source = $projectRoot . '/' . ltrim($path, '/');
        $destination = $this->basePath($path);

        if (is_dir($source)) {
            self::copyDirectory($source, $destination);

            return $this;
        }

        if (is_file($source)) {
            self::copyFile($source, $destination);

            return $this;
        }

        throw TestSupportException::sourceTestPathDoesNotExist($source);
    }

    public function copyFromProjectIfExists(string $path): self
    {
        $projectRoot = \Tests\support\ProjectPaths::root();
        $source = $projectRoot . '/' . ltrim($path, '/');

        if (!file_exists($source)) {
            return $this;
        }

        return $this->copyFromProject($path);
    }

    public function application(): Application
    {
        if ($this->application === null) {
            $this->application = new Application($this->basePath);
        }

        return $this->application;
    }

    public function container(): Container
    {
        return $this->application()->container();
    }

    public function testContainer(): TestContainer
    {
        return TestContainer::forApplication($this->application());
    }

    public function register(ServiceProvider $provider): self
    {
        $this->application()->register($provider);

        return $this;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $abstract
     * @param class-string<T>|Closure(Container):T $concrete
     */
    public function bind(string $abstract, string|Closure $concrete): self
    {
        $this->container()->bind($abstract, $concrete);

        return $this;
    }

    public function instance(string $abstract, object $instance): self
    {
        $this->container()->instance($abstract, $instance);

        return $this;
    }

    /**
     * @param Closure(Application): void $callback
     */
    public function afterBootstrap(Closure $callback): self
    {
        $this->afterBootstrap[] = $callback;

        return $this;
    }

    /**
     * @param array<int, string>|null $argv
     */
    public function bootstrap(?array $argv = null): Application
    {
        self::resetFrameworkState();

        $application = $argv === null
            ? Bootstrap::init($this->basePath)
            : Bootstrap::initForConsole($this->basePath, $argv);
        $this->application = $application;

        foreach ($this->afterBootstrap as $callback) {
            $callback($application);
        }

        return $application;
    }

    private static function createTemporaryDirectory(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . uniqid('', true);

        if (!mkdir($path)) {
            throw TestSupportException::temporaryDirectoryCouldNotBeCreated($path);
        }

        self::$directories[] = $path;

        return $path;
    }

    private static function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, recursive: true)) {
            throw TestSupportException::temporaryDirectoryCouldNotBeCreated($path);
        }
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        self::ensureDirectory($destination);

        $items = scandir($source);

        if ($items === false) {
            throw TestSupportException::testDirectoryCouldNotBeRead($source);
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

    private static function copyFile(string $source, string $destination): void
    {
        self::ensureDirectory(dirname($destination));

        if (!copy($source, $destination)) {
            throw TestSupportException::testFileCouldNotBeCopied($source, $destination);
        }
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw TestSupportException::testDirectoryCouldNotBeRead($directory);
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

    /**
     * @return array<string, string|int|float|bool>
     */
    private function readEnvValues(): array
    {
        if (!is_file($this->envPath())) {
            return [];
        }

        $content = file($this->envPath(), FILE_IGNORE_NEW_LINES);

        if ($content === false) {
            throw TestSupportException::testDirectoryCouldNotBeRead($this->envPath());
        }

        $values = [];

        foreach ($content as $line) {
            if ($line === '' || str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[$key] = $value;
        }

        return $values;
    }

    private function envValue(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
