<?php

declare(strict_types=1);

namespace LPWork\Console\Middleware;

use Closure;
use LPWork\Console\Contracts\ConditionalProductionSensitiveCommand;
use LPWork\Console\Contracts\ConsoleMiddleware;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Responses\ConsoleResponse;

/**
 * Applies production safety middleware middleware behavior.
 */
final readonly class ProductionSafetyMiddleware implements ConsoleMiddleware
{
    /**
     * Creates a new ProductionSafetyMiddleware instance.
     */
    public function __construct(
        private ProductionSensitiveCommand $command,
        private bool $productionEnvironment,
    ) {}

    /**
     * @param Closure(Input): ConsoleResponse $next
     */
    public function handle(Input $input, Closure $next): ConsoleResponse
    {
        if ($this->command instanceof ConditionalProductionSensitiveCommand
            && !$this->command->productionSafetyApplies($input)) {
            return $next($input);
        }

        if ($this->productionEnvironment && !$input->hasOption('force')) {
            return ConsoleResponse::output(
                stderr: $this->command->productionSafetyMessage(),
                exitCode: 1,
            );
        }

        return $next($input);
    }
}
