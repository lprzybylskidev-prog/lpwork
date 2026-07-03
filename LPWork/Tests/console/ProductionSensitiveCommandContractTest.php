<?php

declare(strict_types=1);

use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HasConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use Tests\support\console\ProductionSensitiveCommandContracts;

it('keeps production-sensitive command safety contracts explicit', function (): void {
    $covered = [];

    foreach (ProductionSensitiveCommandContracts::commandClasses() as $commandClass) {
        $command = ProductionSensitiveCommandContracts::instanceWithoutConstructor($commandClass);

        if (!$command instanceof ProductionSensitiveCommand) {
            continue;
        }

        $covered[] = $commandClass;

        expect($command)
            ->toBeInstanceOf(HasConsoleMiddleware::class)
            ->toBeInstanceOf(DescribesInput::class);

        if (!$command instanceof HasConsoleMiddleware || !$command instanceof DescribesInput) {
            continue;
        }

        expect($command->middleware())
            ->toContain(ProductionSafetyMiddleware::class);

        $forceOption = array_values(array_filter(
            $command->options(),
            static fn(ConsoleOption $option): bool => $option->name() === 'force',
        ));

        expect($forceOption)
            ->toHaveCount(1)
            ->and($forceOption[0]->acceptsValue())->toBeFalse()
            ->and($command->productionSafetyMessage())->toContain('--force');
    }

    expect($covered)->not->toBeEmpty();
});
