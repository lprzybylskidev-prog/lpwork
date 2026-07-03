<?php

declare(strict_types=1);

use LPWork\Requests\ConsoleRequest;
use LPWork\Requests\Contracts\Request;

it('wraps console input parsed from argv', function (): void {
    $request = ConsoleRequest::fromArgv(['lpwork', 'preview', 'demo', '--force']);

    expect($request)->toBeInstanceOf(Request::class)
        ->and($request->input()->script())->toBe('lpwork')
        ->and($request->input()->command())->toBe('preview')
        ->and($request->input()->argument(0))->toBe('demo')
        ->and($request->input()->hasOption('force'))->toBeTrue();
});
