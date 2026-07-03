<?php

declare(strict_types=1);

use LPWork\Console\Exceptions\InvalidProgressBarException;
use LPWork\Console\Output;
use LPWork\Console\ProgressBar;
use Tests\support\console\OutputStreams;

it('renders progress as it advances', function (): void {
    $streams = OutputStreams::create();
    $progressBar = new ProgressBar(
        output: new Output($streams->stdout, $streams->stderr, decorated: false),
        total: 4,
        width: 4,
    );

    $progressBar->start();
    $progressBar->advance();
    $progressBar->advance(2);
    $progressBar->finish();

    expect($streams->stdout())->toBe(
        "\r[    ]   0% 0/4"
        . "\r[=   ]  25% 1/4"
        . "\r[=== ]  75% 3/4"
        . "\r[====] 100% 4/4\n",
    );
});

it('treats zero total progress as complete', function (): void {
    $streams = OutputStreams::create();
    $progressBar = new ProgressBar(
        output: new Output($streams->stdout, $streams->stderr, decorated: false),
        total: 0,
        width: 4,
    );

    $progressBar->start();

    expect($streams->stdout())->toBe("\r[====] 100% 0/0");
});

it('throws when total is negative', function (): void {
    $streams = OutputStreams::create();

    expect(fn() => new ProgressBar(
        output: new Output($streams->stdout, $streams->stderr),
        total: -1,
    ))->toThrow(InvalidProgressBarException::class);
});

it('throws when width is not positive', function (): void {
    $streams = OutputStreams::create();

    expect(fn() => new ProgressBar(
        output: new Output($streams->stdout, $streams->stderr),
        total: 10,
        width: 0,
    ))->toThrow(InvalidProgressBarException::class);
});
