<?php

declare(strict_types=1);

use LPWork\Console\Exceptions\InvalidChoiceException;
use LPWork\Console\Output;
use LPWork\Console\Questioner;
use Tests\support\console\OutputStreams;

it('asks a question and returns the answer', function (): void {
    $streams = OutputStreams::create("LPWork\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect($questioner->ask('Project name'))->toBe('LPWork')
        ->and($streams->stdout())->toBe('Project name: ');
});

it('returns default answer when answer is empty', function (): void {
    $streams = OutputStreams::create("\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect($questioner->ask('Project name', 'App'))->toBe('App')
        ->and($streams->stdout())->toBe('Project name [App]: ');
});

it('confirms positive answers', function (): void {
    $streams = OutputStreams::create("yes\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect($questioner->confirm('Continue'))->toBeTrue()
        ->and($streams->stdout())->toBe('Continue [y/N]: ');
});

it('confirms negative answers', function (): void {
    $streams = OutputStreams::create("no\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect($questioner->confirm('Continue', default: true))->toBeFalse()
        ->and($streams->stdout())->toBe('Continue [Y/n]: ');
});

it('uses default confirmation when answer is empty', function (): void {
    $streams = OutputStreams::create("\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect($questioner->confirm('Continue', default: true))->toBeTrue();
});

it('asks for a choice', function (): void {
    $streams = OutputStreams::create("daily\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    $choice = $questioner->choice('Rotation', [
        'daily' => 'Daily',
        'monthly' => 'Monthly',
    ]);

    expect($choice)->toBe('daily')
        ->and($streams->stdout())->toBe('Rotation (daily/monthly): ');
});

it('uses default choice when answer is empty', function (): void {
    $streams = OutputStreams::create("\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    $choice = $questioner->choice('Rotation', [
        'daily' => 'Daily',
        'monthly' => 'Monthly',
    ], default: 'monthly');

    expect($choice)->toBe('monthly')
        ->and($streams->stdout())->toBe('Rotation (daily/monthly) [monthly]: ');
});

it('throws when choice is invalid', function (): void {
    $streams = OutputStreams::create("yearly\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect(fn(): string => $questioner->choice('Rotation', [
        'daily' => 'Daily',
        'monthly' => 'Monthly',
    ]))->toThrow(InvalidChoiceException::class);
});

it('throws when default choice is invalid', function (): void {
    $streams = OutputStreams::create("\n");
    $questioner = new Questioner(
        new Output($streams->stdout, $streams->stderr, decorated: false),
        $streams->stdin,
    );

    expect(fn(): string => $questioner->choice('Rotation', [
        'daily' => 'Daily',
        'monthly' => 'Monthly',
    ], default: 'yearly'))->toThrow(InvalidChoiceException::class);
});
