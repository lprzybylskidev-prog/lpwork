<?php

declare(strict_types=1);

namespace LPWork\Emitters\Providers;

use LPWork\Console\Output;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Emitters\HttpEmitter;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers emitter service provider services with the framework container.
 */
final class EmitterServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(Output::class, static fn(): Output => Output::terminal());
        $container->singleton(ConsoleEmitter::class, static function (Container $container): ConsoleEmitter {
            $output = $container->make(Output::class);

            if (!$output instanceof Output) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Output::class);
            }

            return new ConsoleEmitter($output);
        });
        $container->singleton(HttpEmitter::class, static fn(): HttpEmitter => HttpEmitter::browser());
    }
}
