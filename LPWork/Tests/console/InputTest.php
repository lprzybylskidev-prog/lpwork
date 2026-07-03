<?php

declare(strict_types=1);

use LPWork\Console\Input;

it('parses script command and arguments from argv', function (): void {
    $input = new Input(['lpwork', 'make:test', 'UserTest', '--force']);

    expect($input->script())->toBe('lpwork')
        ->and($input->command())->toBe('make:test')
        ->and($input->hasCommand())->toBeTrue()
        ->and($input->arguments())->toBe(['UserTest'])
        ->and($input->argument(0))->toBe('UserTest')
        ->and($input->argument(1))->toBeNull()
        ->and($input->hasOption('force'))->toBeTrue()
        ->and($input->option('force'))->toBeTrue();
});

it('handles argv without command', function (): void {
    $input = new Input(['lpwork']);

    expect($input->script())->toBe('lpwork')
        ->and($input->command())->toBeNull()
        ->and($input->hasCommand())->toBeFalse()
        ->and($input->arguments())->toBe([]);
});

it('parses long options with values', function (): void {
    $input = new Input(['lpwork', 'make:test', 'UserTest', '--path=tests/unit', '--env', 'testing']);

    expect($input->arguments())->toBe(['UserTest'])
        ->and($input->option('path'))->toBe('tests/unit')
        ->and($input->option('env'))->toBe('testing');
});

it('moves a global module option before the command into command options', function (): void {
    $input = new Input(['lpwork', '--module=Blog', 'make:controller', 'Admin/Dashboard']);

    expect($input->command())->toBe('make:controller')
        ->and($input->hasCommand())->toBeTrue()
        ->and($input->arguments())->toBe(['Admin/Dashboard'])
        ->and($input->option('module'))->toBe('Blog');
});

it('moves a separated global module option before the command into command options', function (): void {
    $input = new Input(['lpwork', '--module', 'Blog', 'make:controller', 'Dashboard']);

    expect($input->command())->toBe('make:controller')
        ->and($input->arguments())->toBe(['Dashboard'])
        ->and($input->option('module'))->toBe('Blog');
});

it('does not treat a standalone global module option as a command', function (): void {
    $input = new Input(['lpwork', '--module=Blog']);

    expect($input->command())->toBe('')
        ->and($input->hasCommand())->toBeFalse()
        ->and($input->option('module'))->toBe('Blog');
});

it('parses negated long options', function (): void {
    $input = new Input(['lpwork', 'command', '--no-interaction']);

    expect($input->hasOption('interaction'))->toBeTrue()
        ->and($input->option('interaction'))->toBeFalse();
});

it('parses short options', function (): void {
    $input = new Input(['lpwork', 'command', '-f', '-p=tests', '-abc', '-vvv']);

    expect($input->option('f'))->toBeTrue()
        ->and($input->option('p'))->toBe('tests')
        ->and($input->option('a'))->toBeTrue()
        ->and($input->option('b'))->toBeTrue()
        ->and($input->option('c'))->toBeTrue()
        ->and($input->option('v'))->toBe(3);
});

it('stops parsing options after double dash', function (): void {
    $input = new Input(['lpwork', 'command', '--force', '--', '--not-option']);

    expect($input->option('force'))->toBeTrue()
        ->and($input->arguments())->toBe(['--not-option']);
});

it('reads multi-value options', function (): void {
    $input = new Input(['lpwork', 'command', '--tag=one', '--tag=two']);

    expect($input->option('tag'))->toBe(['one', 'two'])
        ->and($input->optionValues('tag'))->toBe(['one', 'two'])
        ->and($input->optionValues('missing'))->toBe([]);
});
