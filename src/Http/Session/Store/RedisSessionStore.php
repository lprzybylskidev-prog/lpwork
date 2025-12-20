<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Store;

use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionStorageException;
use LPwork\Http\Session\SessionCookieParameters;
use LPwork\Http\Session\SessionState;
use LPwork\Redis\RedisConnectionManager;
use Predis\ClientInterface;

/**
 * Redis-backed session storage.
 */
class RedisSessionStore implements SessionStoreInterface
{
    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $connections;

    /**
     * @var string
     */
    private string $connectionName;

    /**
     * @var string
     */
    private string $prefix;

    /**
     * @var SessionIdGeneratorInterface
     */
    private SessionIdGeneratorInterface $idGenerator;

    /**
     * @param RedisConnectionManager         $connections
     * @param string                         $connectionName
     * @param string                         $prefix
     * @param SessionIdGeneratorInterface    $idGenerator
     */
    public function __construct(
        RedisConnectionManager $connections,
        string $connectionName,
        string $prefix,
        SessionIdGeneratorInterface $idGenerator,
    ) {
        $this->connections = $connections;
        $this->connectionName = $connectionName;
        $this->prefix = $prefix;
        $this->idGenerator = $idGenerator;
    }

    /**
     * @inheritDoc
     */
    public function start(
        ?string $id,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): SessionState {
        $sessionId = $id ?: $this->idGenerator->generate();
        $key = $this->buildKey($sessionId);

        $payload = $this->client()->get($key);

        if ($payload === null) {
            return new SessionState($sessionId, [], \time());
        }

        $decoded = \json_decode($payload, true);

        if (!\is_array($decoded)) {
            $this->client()->del([$key]);

            return new SessionState($sessionId, [], \time());
        }

        $data = (array) ($decoded["data"] ?? []);

        return new SessionState($sessionId, $data, \time());
    }

    /**
     * @inheritDoc
     */
    public function persist(
        SessionState $state,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): void {
        $key = $this->buildKey($state->id());
        $encoded = \json_encode([
            "data" => $state->all(),
            "last_activity" => $state->lastActivity(),
        ]);

        if ($encoded === false) {
            throw new SessionStorageException(
                "Failed to encode session payload for Redis.",
            );
        }

        $this->client()->setex($key, $lifetime, $encoded);
    }

    /**
     * @inheritDoc
     */
    public function destroy(string $id): void
    {
        $this->client()->del([$this->buildKey($id)]);
    }

    /**
     * @inheritDoc
     */
    public function usesNativeCookie(): bool
    {
        return false;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private function buildKey(string $id): string
    {
        return $this->prefix . $id;
    }

    /**
     * @return ClientInterface
     */
    private function client(): ClientInterface
    {
        return $this->connections->get($this->connectionName)->client();
    }
}
