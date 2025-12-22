<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Store;

use Carbon\CarbonImmutable;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionStorageException;
use LPwork\Http\Session\SessionCookieParameters;
use LPwork\Http\Session\SessionState;
use Psr\Clock\ClockInterface;

/**
 * Native PHP session storage.
 */
class PhpSessionStore implements SessionStoreInterface
{
    /**
     * @var string
     */
    private string $sessionName;

    /**
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @param string          $sessionName
     * @param ClockInterface  $clock
     */
    public function __construct(string $sessionName, ClockInterface $clock)
    {
        $this->sessionName = $sessionName;
        $this->clock = $clock;
    }

    /**
     * @inheritDoc
     */
    public function start(
        ?string $id,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): SessionState {
        if (\session_status() === PHP_SESSION_ACTIVE) {
            \session_write_close();
        }

        $this->configureSession($cookieParameters, $lifetime);

        if ($id !== null && $id !== '') {
            \session_id($id);
        }

        $started = \session_start([
            'cookie_lifetime' => $lifetime,
            'gc_maxlifetime' => $lifetime,
        ]);

        if ($started === false) {
            throw new SessionStorageException('Failed to start PHP session.');
        }

        $data = $_SESSION;
        $sessionId = (string) \session_id();
        \session_write_close();

        return new SessionState(
            $sessionId,
            $data,
            CarbonImmutable::instance($this->clock->now())->getTimestamp(),
        );
    }

    /**
     * @inheritDoc
     */
    public function persist(
        SessionState $state,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): void {
        $status = \session_status();

        if ($status === PHP_SESSION_ACTIVE) {
            if (\session_id() !== $state->id()) {
                \session_write_close();
                \session_id($state->id());
                $this->configureSession($cookieParameters, $lifetime);
                \session_start([
                    'cookie_lifetime' => $lifetime,
                    'gc_maxlifetime' => $lifetime,
                ]);
            }
        } else {
            $this->configureSession($cookieParameters, $lifetime);
            \session_id($state->id());
            \session_start([
                'cookie_lifetime' => $lifetime,
                'gc_maxlifetime' => $lifetime,
            ]);
        }

        $_SESSION = $state->all();
        \session_write_close();
    }

    /**
     * @inheritDoc
     */
    public function destroy(string $id): void
    {
        if (\session_status() === PHP_SESSION_ACTIVE) {
            \session_write_close();
        }

        \session_id($id);
        \session_start();
        $_SESSION = [];
        \session_destroy();
    }

    /**
     * @inheritDoc
     */
    public function usesNativeCookie(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function cleanupExpired(int $lifetime): void
    {
        // Native PHP session handler performs its own garbage collection.
    }

    /**
     * @param SessionCookieParameters $cookieParameters
     * @param int                     $lifetime
     *
     * @return void
     */
    private function configureSession(
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): void {
        if (\session_status() !== PHP_SESSION_ACTIVE) {
            \session_name($this->sessionName);
        }

        \session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $cookieParameters->path(),
            'domain' => $cookieParameters->domain(),
            'secure' => $cookieParameters->secure(),
            'httponly' => $cookieParameters->httpOnly(),
            'samesite' => \ucfirst(\strtolower($cookieParameters->sameSite())),
        ]);
    }
}
