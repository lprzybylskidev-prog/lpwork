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

/**
 * Represents the json log formatter framework component.
 */
final class JsonLogFormatter implements LogFormatter
{
    /**
     * Builds or returns format.
     */
    public function format(LogRecord $record): string
    {
        try {
            return json_encode($this->normalize($record), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return '{"message":"Could not encode log record."}';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(LogRecord $record): array
    {
        return [
            'datetime' => $record->datetime->format('Y-m-d H:i:s'),
            'channel' => $record->channel,
            'level' => $record->level->value,
            'message' => $record->message,
            'context' => $record->context,
        ];
    }
}
