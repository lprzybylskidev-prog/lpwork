<?php

declare(strict_types=1);

use LPWork\ErrorHandling\ErrorHandler;

it('converts PHP errors to exceptions', function (): void {
    $oldErrorReporting = error_reporting(E_ALL);

    $handler = new ErrorHandler();

    try {
        $handler->handle(
            severity: E_WARNING,
            message: 'Test warning',
            file: __FILE__,
            line: 123
        );
    } finally {
        error_reporting($oldErrorReporting);
    }
})->throws(ErrorException::class, 'Test warning');
