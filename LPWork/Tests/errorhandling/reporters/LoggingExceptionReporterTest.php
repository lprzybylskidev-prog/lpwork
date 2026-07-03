<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Reporters\LoggingExceptionReporter;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;

it('reports exceptions to the error log level with context', function (): void {
    $logger = new class implements Logger {
        public ?LogLevel $level = null;

        public ?string $message = null;

        /**
         * @var array<string, mixed>
         */
        public array $context = [];

        /**
         * @param array<string, mixed> $context
         */
        public function log(LogLevel $level, string $message, array $context = []): void
        {
            $this->level = $level;
            $this->message = $message;
            $this->context = $context;
        }

        /**
         * @param array<string, mixed> $context
         */
        public function debug(string $message, array $context = []): void
        {
            $this->log(LogLevel::Debug, $message, $context);
        }

        /**
         * @param array<string, mixed> $context
         */
        public function info(string $message, array $context = []): void
        {
            $this->log(LogLevel::Info, $message, $context);
        }

        /**
         * @param array<string, mixed> $context
         */
        public function notice(string $message, array $context = []): void
        {
            $this->log(LogLevel::Notice, $message, $context);
        }

        /**
         * @param array<string, mixed> $context
         */
        public function warning(string $message, array $context = []): void
        {
            $this->log(LogLevel::Warning, $message, $context);
        }

        /**
         * @param array<string, mixed> $context
         */
        public function error(string $message, array $context = []): void
        {
            $this->log(LogLevel::Error, $message, $context);
        }

        /**
         * @param array<string, mixed> $context
         */
        public function critical(string $message, array $context = []): void
        {
            $this->log(LogLevel::Critical, $message, $context);
        }
    };

    $throwable = new RuntimeException('Boom');

    new LoggingExceptionReporter($logger)->report($throwable);

    expect($logger->level)->toBe(LogLevel::Error)
        ->and($logger->message)->toBe('Boom')
        ->and($logger->context['exception'])->toBe(RuntimeException::class)
        ->and($logger->context['file'])->toBe($throwable->getFile())
        ->and($logger->context['line'])->toBe($throwable->getLine())
        ->and($logger->context['trace'])->toBe($throwable->getTraceAsString());
});
