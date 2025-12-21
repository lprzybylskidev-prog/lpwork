<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Contract;

use LPwork\Http\Session\SessionCookieParameters;
use LPwork\Http\Session\SessionState;

/**
 * Persists session state using configured backend.
 */
interface SessionStoreInterface
{
    /**
     * Starts session and returns current state.
     *
     * @param string|null               $id
     * @param SessionCookieParameters   $cookieParameters
     * @param int                       $lifetime
     *
     * @return SessionState
     */
    public function start(
        ?string $id,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): SessionState;

    /**
     * Persists session state.
     *
     * @param SessionState             $state
     * @param SessionCookieParameters  $cookieParameters
     * @param int                      $lifetime
     *
     * @return void
     */
    public function persist(
        SessionState $state,
        SessionCookieParameters $cookieParameters,
        int $lifetime,
    ): void;

    /**
     * Destroys session for given identifier.
     *
     * @param string $id
     *
     * @return void
     */
    public function destroy(string $id): void;

    /**
     * Cleans up expired session entries in backend storage.
     *
     * @param int $lifetime
     *
     * @return void
     */
    public function cleanupExpired(int $lifetime): void;

    /**
     * Indicates if storage handles cookie emission by itself (e.g. native PHP).
     *
     * @return bool
     */
    public function usesNativeCookie(): bool;
}
