<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Store;

use LPwork\Filesystem\FilesystemManager;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionStorageException;
use LPwork\Http\Session\SessionCookieParameters;
use LPwork\Http\Session\SessionState;
use League\Flysystem\FilesystemOperator;

/**
 * Filesystem-based session storage.
 */
class FilesystemSessionStore implements SessionStoreInterface
{
    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystems;

    /**
     * @var string
     */
    private string $disk;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var SessionIdGeneratorInterface
     */
    private SessionIdGeneratorInterface $idGenerator;

    /**
     * @param FilesystemManager           $filesystems
     * @param string                      $disk
     * @param string                      $path
     * @param SessionIdGeneratorInterface $idGenerator
     */
    public function __construct(
        FilesystemManager $filesystems,
        string $disk,
        string $path,
        SessionIdGeneratorInterface $idGenerator,
    ) {
        $this->filesystems = $filesystems;
        $this->disk = $disk;
        $this->path = \trim($path, "/");
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
        $location = $this->location($sessionId);
        $filesystem = $this->filesystem();

        if (!$filesystem->fileExists($location)) {
            return new SessionState($sessionId, [], \time());
        }

        $contents = $filesystem->read($location);
        $decoded = \json_decode($contents, true);

        if (!\is_array($decoded)) {
            $filesystem->delete($location);

            return new SessionState($sessionId, [], \time());
        }

        $expiresAt = (int) ($decoded["expires_at"] ?? 0);

        if ($expiresAt > 0 && $expiresAt < \time()) {
            $filesystem->delete($location);

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
        $filesystem = $this->filesystem();
        $location = $this->location($state->id());

        $payload = \json_encode([
            "data" => $state->all(),
            "last_activity" => $state->lastActivity(),
            "expires_at" => \time() + $lifetime,
        ]);

        if ($payload === false) {
            throw new SessionStorageException(
                "Failed to encode session payload for filesystem.",
            );
        }

        if ($this->path !== "" && !$filesystem->directoryExists($this->path)) {
            $filesystem->createDirectory($this->path);
        }

        $filesystem->write($location, $payload);
    }

    /**
     * @inheritDoc
     */
    public function destroy(string $id): void
    {
        $filesystem = $this->filesystem();
        $location = $this->location($id);

        if ($filesystem->fileExists($location)) {
            $filesystem->delete($location);
        }
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
    private function location(string $id): string
    {
        if ($this->path === "") {
            return $id . ".json";
        }

        return $this->path . "/" . $id . ".json";
    }

    /**
     * @return FilesystemOperator
     */
    private function filesystem(): FilesystemOperator
    {
        return $this->filesystems->disk($this->disk);
    }
}
