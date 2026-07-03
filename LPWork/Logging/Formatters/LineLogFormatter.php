<?php

declare(strict_types=1);

namespace LPWork\Logging\Formatters;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use JsonException;
use LPWork\Logging\Contracts\LogFormatter;
use LPWork\Logging\LogRecord;

use function sprintf;

/**
 * Represents the line log formatter framework component.
 */
final class LineLogFormatter implements LogFormatter
{
    /**
     * Builds or returns format.
     */
    public function format(LogRecord $record): string
    {
        return sprintf(
            '[%s] %s.%s: %s%s',
            $record->datetime->format('Y-m-d H:i:s'),
            $record->channel,
            $record->level->value,
            $record->message,
            $this->formatContext($record->context),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatContext(array $context): string
    {
        if ($context === []) {
            return '';
        }

        try {
            return ' ' . json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return ' {"context":"unserializable"}';
        }
    }
}
