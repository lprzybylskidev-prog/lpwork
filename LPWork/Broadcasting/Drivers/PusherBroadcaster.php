<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Drivers;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\BroadcastResult;
use LPWork\Broadcasting\Contracts\Broadcaster;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastingConfigException;
use LPWork\Shared\Http\HttpClient;

/**
 * Represents the pusher broadcaster framework component.
 */
final readonly class PusherBroadcaster implements Broadcaster
{
    /**
     * Creates a new PusherBroadcaster instance.
     */
    public function __construct(
        private string $name,
        private string $appId,
        private string $key,
        private string $secret,
        private string $endpoint,
        private HttpClient $http = new HttpClient(),
    ) {}

    /**
     * Runs broadcast.
     */
    public function broadcast(BroadcastMessage $message): BroadcastResult
    {
        $body = json_encode([
            'name' => $message->name(),
            'channels' => $message->channels(),
            'data' => json_encode($message->payload(), JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);
        $path = "/apps/{$this->appId}/events";
        $query = [
            'auth_key' => $this->key,
            'auth_timestamp' => (string) time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];
        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', "POST\n{$path}\n{$queryString}", $this->secret);
        $url = rtrim($this->endpoint, '/') . $path . '?' . $queryString . '&auth_signature=' . $signature;
        $response = $this->http->request('POST', $url, ['Content-Type' => 'application/json'], $body);

        if (!$response->successful()) {
            throw new InvalidBroadcastingConfigException('pusher.response');
        }

        return new BroadcastResult($this->name, $message->name(), $message->channels());
    }
}
