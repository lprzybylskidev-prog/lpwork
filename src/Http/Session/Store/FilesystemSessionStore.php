<?php
declare(strict_types=1);

namespace LPwork\Http\Session\Store;

use Carbon\CarbonImmutable;
use LPwork\Filesystem\FilesystemManager;
use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Contract\SessionStoreInterface;
use LPwork\Http\Session\Exception\SessionStorageException;
use LPwork\Http\Session\SessionCookieParameters;
use LPwork\Http\Session\SessionState;
use League\Flysystem\FilesystemOperator;
use Psr\Clock\ClockInterface;

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
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @param FilesystemManager           $filesystems
     * @param string                      $disk
     * @param string                      $path
     * @param SessionIdGeneratorInterface $idGenerator
     * @param ClockInterface              $clock
     */
    public function __construct(
        FilesystemManager $filesystems,
        string $disk,
        string $path,
        SessionIdGeneratorInterface $idGenerator,
        ClockInterface $clock,
    ) {
        $this->filesystems = $filesystems;
        $this->disk = $disk;
        $this->path = \trim($path, '/');
        $this->idGenerator = $idGenerator;
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
        $sessionId = $id ?: $this->idGenerator->generate();
        $now = $this->now();
        $nowTimestamp = $now->getTimestamp();
        $location = $this->location($sessionId);
        $filesystem = $this->filesystem();

        if (!$filesystem->fileExists($location)) {
            return new SessionState($sessionId, [], $nowTimestamp);
        }

        $contents = $filesystem->read($location);
        $decoded = \json_decode($contents, true);

        if (!\is_array($decoded)) {
            $filesystem->delete($location);

            return new SessionState($sessionId, [], $nowTimestamp);
        }

        $expiresAt = (int) ($decoded['expires_at'] ?? 0);

        if ($expiresAt > 0 && $expiresAt < $nowTimestamp) {
            $filesystem->delete($location);

            return new SessionState($sessionId, [], $nowTimestamp);
        }

        $data = (array) ($decoded['data'] ?? []);
        $lastActivity = (int) ($decoded['last_activity'] ?? $nowTimestamp);

        return new SessionState($sessionId, $data, $lastActivity);
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
        $now = $this->now();

        $payload = \json_encode([
            'data' => $state->all(),
            'last_activity' => $state->lastActivity(),
            'expires_at' => $now->addSeconds($lifetime)->getTimestamp(),
        ]);

        if ($payload === false) {
            throw new SessionStorageException('Failed to encode session payload for filesystem.');
        }

        if ($this->path !== '' && !$filesystem->directoryExists($this->path)) {
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
     * @inheritDoc
     */
    public function cleanupExpired(int $lifetime): void
    {
        $filesystem = $this->filesystem();
        $directory = $this->path;
        $nowTimestamp = $this->now()->getTimestamp();

        if ($directory !== '' && !$filesystem->directoryExists($directory)) {
            return;
        }

        foreach ($filesystem->listContents($directory, false) as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $location = $item->path();
            $contents = $filesystem->read($location);
            $decoded = \json_decode($contents, true);

            if (!\is_array($decoded)) {
                $filesystem->delete($location);

                continue;
            }

            $expiresAt = (int) ($decoded['expires_at'] ?? 0);

            if ($expiresAt > 0 && $expiresAt < $nowTimestamp) {
                $filesystem->delete($location);
            }
        }
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private function location(string $id): string
    {
        if ($this->path === '') {
            return $id . '.json';
        }

        return $this->path . '/' . $id . '.json';
    }

    /**
     * @return FilesystemOperator
     */
    private function filesystem(): FilesystemOperator
    {
        return $this->filesystems->disk($this->disk);
    }

    /**
     * @return CarbonImmutable
     */
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->clock->now());
    }
}
