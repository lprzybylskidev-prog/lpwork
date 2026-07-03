<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Exceptions\ConfigAlreadyInitializedException;
use LPWork\Config\Exceptions\ConfigCacheWriteException;
use LPWork\Config\Exceptions\DirectoryNotFoundException;
use LPWork\Config\Exceptions\InvalidFileException;
use LPWork\Config\Exceptions\InvalidKeyException;
use LPWork\Config\Exceptions\InvalidValueException;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Shared\Exceptions\SingletonInstanceException;
use Tests\support\ConfigTestFiles;

beforeEach(function (): void {
    Config::reset();
    ConfigTestFiles::resetDirectory();
});

afterEach(function (): void {
    Config::reset();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('throws when config is used before initialization', function (): void {
    expect(fn(): string => Config::getString('app.value'))
        ->toThrow(SingletonInstanceException::class);
});

it('throws when config is initialized more than once', function (): void {
    $firstDirectory = ConfigTestFiles::directory();
    $secondDirectory = ConfigTestFiles::createDirectory();
    $firstFile = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'First'];\n", $firstDirectory);
    $secondFile = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Second'];\n", $secondDirectory);

    try {
        Config::init($firstDirectory);

        expect(fn() => Config::init($secondDirectory))
            ->toThrow(ConfigAlreadyInitializedException::class);
    } finally {
        unlink($firstFile);
        unlink($secondFile);
    }
});

it('can be reset and initialized again', function (): void {
    $firstDirectory = ConfigTestFiles::directory();
    $secondDirectory = ConfigTestFiles::createDirectory();
    $firstFile = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'First'];\n", $firstDirectory);
    $secondFile = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Second'];\n", $secondDirectory);

    try {
        Config::init($firstDirectory);
        Config::reset();
        Config::init($secondDirectory);

        expect(Config::getString('app.name'))->toBe('Second');
    } finally {
        unlink($firstFile);
        unlink($secondFile);
    }
});

it('throws when config key is invalid', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test'];\n");

    try {
        Config::init($directory);

        expect(fn(): string => Config::getString('app name'))
        ->toThrow(InvalidKeyException::class);
    } finally {
        unlink($file);
    }
});

it('throws when requested variable is missing', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test'];\n");

    try {
        Config::init($directory);

        expect(fn(): string => Config::getString('app.neme'))
            ->toThrow(MissingVariableException::class);
    } finally {
        unlink($file);
    }
});

it('throws when value cannot be cast to requested type', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test'];\n");

    try {
        Config::init($directory);

        expect(fn(): int => Config::getInt('app.name'))
            ->toThrow(InvalidValueException::class);
    } finally {
        unlink($file);
    }
});

it('casts string values', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test'];\n");

    try {
        Config::init($directory);
        expect(Config::getString('app.name'))->toBe('Test');
    } finally {
        unlink($file);
    }
});

it('casts integer values and accepts leading zeroes', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['value_1' => 100, 'value_2' => 01, 'value_3' => -10, 'value_4' => '20'];\n");

    try {
        Config::init($directory);
        expect(Config::getInt('app.value_1'))->toBe(100)
            ->and(Config::getInt('app.value_2'))->toBe(1)
            ->and(Config::getInt('app.value_3'))->toBe(-10)
            ->and(Config::getInt('app.value_4'))->toBe(20);
    } finally {
        unlink($file);
    }
});

it('casts float values', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['value_1' => 0.1, 'value_2' => '0.2'];\n");

    try {
        Config::init($directory);
        expect(Config::getFloat('app.value_1'))->toBe(0.1)
            ->and(Config::getFloat('app.value_2'))->toBe(0.2);
    } finally {
        unlink($file);
    }
});

it('casts boolean values', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['value_1' => true, 'value_2' => 0, 'value_3' => 'true'];\n");

    try {
        Config::init($directory);
        expect(Config::getBool('app.value_1'))->toBe(true)
            ->and(Config::getBool('app.value_2'))->toBe(false)
            ->and(Config::getBool('app.value_3'))->toBe(true);
    } finally {
        unlink($file);
    }
});

it('casts array values', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['value_1' => [0,1,2], 'value_2' => ['value_1' => 1, 'value_2' => 2]];\n");

    try {
        Config::init($directory);
        expect(Config::getArray('app.value_1'))->toBe([0,1,2])
            ->and(Config::getArray('app.value_2'))->toBe(['value_1' => 1, 'value_2' => 2]);
    } finally {
        unlink($file);
    }
});

it('throws when config file does not return an array', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn 1;\n");

    try {
        expect(fn() => Config::init($directory))
            ->toThrow(InvalidFileException::class);
    } finally {
        unlink($file);
    }
});

it('loads values from multiple configuration files', function (): void {
    $directory = ConfigTestFiles::directory();

    $file1 = ConfigTestFiles::createFile('test1.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test1'];\n");
    $file2 = ConfigTestFiles::createFile('test2.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test2'];\n");

    try {
        Config::init($directory);
        expect(Config::getString('test1.name'))->toBe('Test1');
        expect(Config::getString('test2.name'))->toBe('Test2');
    } finally {
        unlink($file1);
        unlink($file2);
    }
});

it('returns all loaded configuration values', function (): void {
    $directory = ConfigTestFiles::directory();
    $file1 = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test'];\n");
    $file2 = ConfigTestFiles::createFile('security.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app_key' => 'secret'];\n");

    try {
        Config::init($directory);

        expect(Config::all())->toBe([
            'app' => ['name' => 'Test'],
            'security' => ['app_key' => 'secret'],
        ]);
    } finally {
        unlink($file1);
        unlink($file2);
    }
});

it('loads values from explicitly provided configuration files', function (): void {
    $directory = ConfigTestFiles::directory();

    $file1 = ConfigTestFiles::createFile('test1.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test1'];\n");
    $file2 = ConfigTestFiles::createFile('test2.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test2'];\n");

    try {
        Config::initFiles([$file1, $file2]);

        expect(Config::getString('test1.name'))->toBe('Test1');
        expect(Config::getString('test2.name'))->toBe('Test2');
    } finally {
        unlink($file1);
        unlink($file2);
    }
});

it('throws when explicit config files are initialized more than once', function (): void {
    $firstDirectory = ConfigTestFiles::directory();
    $secondDirectory = ConfigTestFiles::createDirectory();
    $firstFile = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'First'];\n", $firstDirectory);
    $secondFile = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Second'];\n", $secondDirectory);

    try {
        Config::initFiles([$firstFile]);

        expect(fn() => Config::initFiles([$secondFile]))
            ->toThrow(ConfigAlreadyInitializedException::class);
    } finally {
        unlink($firstFile);
        unlink($secondFile);
    }
});

it('throws when an explicitly provided configuration file does not exist', function (): void {
    $path = ConfigTestFiles::directory() . '/missing.php';

    expect(fn() => Config::initFiles([$path]))
        ->toThrow(LPWork\Config\Exceptions\FileNotFoundException::class);
});

it('loads a value from a nested key', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['values' => ['test' => 'Test']];\n");

    try {
        Config::init($directory);
        expect(Config::getString('app.values.test'))->toBe('Test');
    } finally {
        unlink($file);
    }
});

it('checks whether keys exist and returns defaults for missing generic values', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Test', 'debug' => false];\n");

    try {
        Config::init($directory);

        expect(Config::has('app.name'))->toBeTrue()
            ->and(Config::has('app.missing'))->toBeFalse()
            ->and(Config::get('app.name'))->toBe('Test')
            ->and(Config::get('app.missing', 'Fallback'))->toBe('Fallback')
            ->and(Config::get('app.debug'))->toBeFalse();
    } finally {
        unlink($file);
    }
});

it('resolves namespaced module config before falling back to global config', function (): void {
    Config::initDefinitions([
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'app';
            }

            public function values(): array
            {
                return [
                    'name' => 'Main application',
                    'timezone' => 'UTC',
                ];
            }
        },
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'welcome';
            }

            public function values(): array
            {
                return [
                    'app' => [
                        'name' => 'Welcome module',
                    ],
                ];
            }
        },
    ]);

    expect(Config::getString('welcome::app.name'))->toBe('Welcome module')
        ->and(Config::getString('welcome::app.timezone'))->toBe('UTC')
        ->and(Config::has('welcome::app.name'))->toBeTrue()
        ->and(Config::has('welcome::app.timezone'))->toBeTrue();
});

it('keeps direct global config access explicit when modules define matching keys', function (): void {
    Config::initDefinitions([
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'app';
            }

            public function values(): array
            {
                return [
                    'name' => 'Main application',
                ];
            }
        },
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'welcome';
            }

            public function values(): array
            {
                return [
                    'app' => [
                        'name' => 'Welcome module',
                    ],
                ];
            }
        },
    ]);

    expect(Config::getString('app.name'))->toBe('Main application');
});

it('merges repeated config definition keys deterministically', function (): void {
    Config::initDefinitions([
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'welcome';
            }

            public function values(): array
            {
                return [
                    'app' => [
                        'name' => 'Welcome module',
                        'timezone' => 'UTC',
                    ],
                ];
            }
        },
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'welcome';
            }

            public function values(): array
            {
                return [
                    'app' => [
                        'name' => 'Overridden welcome module',
                    ],
                ];
            }
        },
    ]);

    expect(Config::getString('welcome::app.name'))->toBe('Overridden welcome module')
        ->and(Config::getString('welcome::app.timezone'))->toBe('UTC');
});

it('writes and loads cached configuration', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Cached'];\n");
    $cachePath = ConfigTestFiles::createDirectory() . '/config.php';

    try {
        Config::init($directory);
        Config::writeCache($cachePath);
        Config::reset();
        unlink($file);

        Config::initCached($cachePath);

        expect(Config::getString('app.name'))->toBe('Cached');
    } finally {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

it('throws a config cache write exception when cache file cannot be written', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Cached'];\n");

    try {
        Config::init($directory);

        expect(fn() => Config::writeCache('php://temp'))
            ->toThrow(ConfigCacheWriteException::class);
    } finally {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

it('writes and loads cached namespaced module configuration', function (): void {
    $cachePath = ConfigTestFiles::createDirectory() . '/config.php';

    Config::initDefinitions([
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'app';
            }

            public function values(): array
            {
                return [
                    'name' => 'Main application',
                    'timezone' => 'UTC',
                ];
            }
        },
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'welcome';
            }

            public function values(): array
            {
                return [
                    'app' => [
                        'name' => 'Welcome module',
                    ],
                ];
            }
        },
    ]);

    Config::writeCache($cachePath);
    Config::reset();
    Config::initCached($cachePath);

    expect(Config::getString('welcome::app.name'))->toBe('Welcome module')
        ->and(Config::getString('welcome::app.timezone'))->toBe('UTC')
        ->and(Config::getString('app.name'))->toBe('Main application');
});

it('throws when config directory does not exist', function (): void {
    expect(fn() => Config::init('/this/directory/does/not/exists'))
            ->toThrow(DirectoryNotFoundException::class);
});

it('throws when value is not a string', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 1];\n");

    try {
        Config::init($directory);
        expect(fn() => Config::getString('app.name'))
            ->toThrow(InvalidValueException::class);
    } finally {
        unlink($file);
    }
});

it('throws when value is not an array', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createFile('app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 1];\n");

    try {
        Config::init($directory);
        expect(fn() => Config::getArray('app.name'))
            ->toThrow(InvalidValueException::class);
    } finally {
        unlink($file);
    }
});
