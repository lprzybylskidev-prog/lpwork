<?php
declare(strict_types=1);

namespace LPwork\Logging\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level as MonologLevel;
use Predis\ClientInterface;

/**
 * Pushes log records to a Redis list using Predis client.
 */
class RedisLogHandler extends AbstractProcessingHandler
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var string
     */
    private string $key;

    /**
     * @param ClientInterface $client
     * @param string          $key
     * @param MonologLevel    $level
     * @param bool            $bubble
     */
    public function __construct(
        ClientInterface $client,
        string $key,
        MonologLevel $level,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
        $this->client = $client;
        $this->key = $key;
    }

    /**
     * @return void
     */
    protected function write(\Monolog\LogRecord $record): void
    {
        $payload = $this->jsonEncode([
            'channel' => $record->channel,
            'level' => $record->level->value,
            'message' => $record->message,
            'context' => $record->context,
            'extra' => $record->extra,
            'datetime' => $record->datetime->format(\DateTimeInterface::ATOM),
        ]);

        $this->client->rpush($this->key, [$payload]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     */
    private function jsonEncode(array $payload): string
    {
        $encoded = \json_encode($payload);

        if ($encoded === false) {
            return '{}';
        }

        return $encoded;
    }
}
