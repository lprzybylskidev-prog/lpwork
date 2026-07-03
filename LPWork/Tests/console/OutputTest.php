<?php

declare(strict_types=1);

use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;
use LPWork\Console\Output;
use Tests\support\console\OutputStreams;

it('writes to stdout', function (): void {
    $streams = OutputStreams::create();

    $output = new Output($streams->stdout, $streams->stderr);

    $output->write('Hello');
    $output->writeln(' world');

    expect($streams->stdout())->toBe("Hello world\n")
        ->and($streams->stderr())->toBe('');
});

it('writes errors to stderr', function (): void {
    $streams = OutputStreams::create();

    $output = new Output($streams->stdout, $streams->stderr);

    $output->error('Nope');

    expect($streams->stdout())->toBe('')
        ->and($streams->stderr())->toBe("Nope\n");
});

it('formats messages with ansi colors and styles', function (): void {
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr);

    $message = $output->format(
        'Success',
        foreground: ConsoleColor::Green,
        background: ConsoleColor::Black,
        styles: [ConsoleStyle::Bold],
    );

    expect($message)->toBe("\033[1;32;40mSuccess\033[0m");
});

it('formats messages with the LPWork brand color', function (): void {
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr);

    $message = $output->format('LPWork', foreground: ConsoleColor::LpworkBlue);

    expect($message)->toBe("\033[38;2;66;136;206mLPWork\033[0m");
});

it('does not format messages when decorations are disabled', function (): void {
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr, decorated: false);

    $message = $output->format(
        'Success',
        foreground: ConsoleColor::Green,
        styles: [ConsoleStyle::Bold],
    );

    expect($message)->toBe('Success');
});

it('writes formatted messages to stdout', function (): void {
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr);

    $output->writelnFormatted('Warning', foreground: ConsoleColor::Yellow);

    expect($streams->stdout())->toBe("\033[33mWarning\033[0m\n");
});
