<?php
declare(strict_types=1);

namespace LPwork\Config;

use LPwork\Config\Exception\ConfigFileInvalidException;
use LPwork\Environment\Env;

/**
 * Loads PHP configuration files returning arrays.
 */
class PhpConfigLoader
{
    private Env $env;

    /**
     * @param Env $env
     */
    public function __construct(Env $env)
    {
        $this->env = $env;
    }

    /**
     * Loads all PHP config files from a directory.
     *
     * @param string $directory
     *
     * @return array<string, array<string, mixed>>
     */
    public function loadDirectory(string $directory): array
    {
        if (!\is_dir($directory)) {
            return [];
        }

        $configs = [];

        /** @var array<int, string> $files */
        $files = \glob(\rtrim($directory, '/\\') . '/*.php') ?: [];

        foreach ($files as $file) {
            $configName = \basename($file, '.php');
            $configs[$configName] = $this->loadFile($file);
        }

        return $configs;
    }

    /**
     * @param string $file
     *
     * @return array<string, mixed>
     */
    private function loadFile(string $file): array
    {
        /** @var array<string, mixed> $config */
        $config = (static function (Env $env, string $path): array {
            return require $path;
        })($this->env, $file);

        if (!\is_array($config)) {
            throw new ConfigFileInvalidException(\sprintf('Config file "%s" must return an array.', $file));
        }

        return $config;
    }
}
