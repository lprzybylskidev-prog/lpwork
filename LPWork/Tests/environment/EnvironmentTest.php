<?php

declare(strict_types=1);

use LPWork\Environment\Environment;
use LPWork\Environment\Exceptions\EnvironmentAlreadyInitializedException;
use LPWork\Environment\Exceptions\FileNotFoundException;
use LPWork\Environment\Exceptions\InvalidLineStructureException;
use LPWork\Environment\Exceptions\InvalidValueException;
use LPWork\Environment\Exceptions\MissingVariableException;
use LPWork\Shared\Exceptions\SingletonInstanceException;
use Tests\support\EnvironmentTestFiles;

beforeEach(function (): void {
    Environment::reset();
    EnvironmentTestFiles::resetFile();
});

afterEach(function (): void {
    Environment::reset();
});

afterAll(function (): void {
    EnvironmentTestFiles::removeFiles();
});

it('throws when environment is used before initialization', function (): void {
    expect(fn(): string => Environment::getString('APP_NAME'))
        ->toThrow(SingletonInstanceException::class);
});

it('throws when environment file does not exist', function (): void {
    expect(function (): void {
        Environment::init(sys_get_temp_dir() . '/missing-lpwork-env-file');
    })
        ->toThrow(FileNotFoundException::class);
});

it('throws when environment is initialized more than once', function (): void {
    $firstPath = EnvironmentTestFiles::file();
    $secondPath = EnvironmentTestFiles::createFile('APP_NAME=Second');

    EnvironmentTestFiles::appendValue('APP_NAME', 'First', $firstPath);

    Environment::init($firstPath);

    expect(fn() => Environment::init($secondPath))
        ->toThrow(EnvironmentAlreadyInitializedException::class);
});

it('can be reset and initialized again', function (): void {
    $firstPath = EnvironmentTestFiles::file();
    $secondPath = EnvironmentTestFiles::createFile('APP_NAME=Second');

    EnvironmentTestFiles::appendValue('APP_NAME', 'First', $firstPath);

    Environment::init($firstPath);
    Environment::reset();
    Environment::init($secondPath);

    expect(Environment::getString('APP_NAME'))->toBe('Second');
});

it('reads string values and ignores empty lines and comments', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'

            # Application settings
            APP_NAME = LPWork
            EMPTY_VALUE=
            QUOTED_VALUE = "hello world"
            SINGLE_QUOTED_VALUE = 'hello world'
            ENV,
    );

    Environment::init($path);

    expect(Environment::getString('APP_NAME'))->toBe('LPWork')
        ->and(Environment::getString('EMPTY_VALUE'))->toBe('')
        ->and(Environment::getString('QUOTED_VALUE'))->toBe('hello world')
        ->and(Environment::getString('SINGLE_QUOTED_VALUE'))->toBe('hello world');
});

it('checks whether keys exist and returns defaults for missing values', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendValue('APP_NAME', 'LPWork');

    Environment::init($path);

    expect(Environment::has('APP_NAME'))->toBeTrue()
        ->and(Environment::has('MISSING_VALUE'))->toBeFalse()
        ->and(Environment::get('APP_NAME'))->toBe('LPWork')
        ->and(Environment::get('MISSING_VALUE', 'Fallback'))->toBe('Fallback');
});

it('unescapes matching quotes inside quoted values', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'
            DOUBLE_QUOTE = "say \"hello\""
            SINGLE_QUOTE = 'it\'s ok'
            ENV,
    );

    Environment::init($path);

    expect(Environment::getString('DOUBLE_QUOTE'))->toBe('say "hello"')
        ->and(Environment::getString('SINGLE_QUOTE'))->toBe("it's ok");
});

it('casts integer values and accepts leading zeroes', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'
            PORT = 8080
            LEADING_ZERO = 01
            NEGATIVE_VALUE = -10
            ENV,
    );

    Environment::init($path);

    expect(Environment::getInt('PORT'))->toBe(8080)
        ->and(Environment::getInt('LEADING_ZERO'))->toBe(1)
        ->and(Environment::getInt('NEGATIVE_VALUE'))->toBe(-10);
});

it('casts float values', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'
            PRICE = 12.50
            INTEGER_AS_FLOAT = 10
            ENV,
    );

    Environment::init($path);

    expect(Environment::getFloat('PRICE'))->toBe(12.5)
        ->and(Environment::getFloat('INTEGER_AS_FLOAT'))->toBe(10.0);
});

it('casts boolean values', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'
            TRUE_VALUE = true
            FALSE_VALUE = false
            YES_VALUE = yes
            NO_VALUE = no
            ONE_VALUE = 1
            ZERO_VALUE = 0
            ENV,
    );

    Environment::init($path);

    expect(Environment::getBool('TRUE_VALUE'))->toBeTrue()
        ->and(Environment::getBool('FALSE_VALUE'))->toBeFalse()
        ->and(Environment::getBool('YES_VALUE'))->toBeTrue()
        ->and(Environment::getBool('NO_VALUE'))->toBeFalse()
        ->and(Environment::getBool('ONE_VALUE'))->toBeTrue()
        ->and(Environment::getBool('ZERO_VALUE'))->toBeFalse();
});

it('throws when requested variable is missing', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendValue('APP_NAME', 'LPWork');

    Environment::init($path);

    expect(fn(): string => Environment::getString('MISSING_VALUE'))
        ->toThrow(MissingVariableException::class);
});

it('throws when value cannot be cast to requested type', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'
            PORT = abc
            ENABLED = maybe
            ENV,
    );

    Environment::init($path);

    expect(fn(): int => Environment::getInt('PORT'))
        ->toThrow(InvalidValueException::class)
        ->and(fn(): bool => Environment::getBool('ENABLED'))
        ->toThrow(InvalidValueException::class);
});

it('reports real line number when env structure is invalid after empty lines', function (): void {
    $path = EnvironmentTestFiles::file();

    EnvironmentTestFiles::appendLine(
        <<<'ENV'
            APP_NAME=LPWork

            INVALID LINE
            ENV,
    );

    expect(function () use ($path): void {
        Environment::init($path);
    })
        ->toThrow(InvalidLineStructureException::class, 'Invalid .env structure on line 3: INVALID LINE');
});
