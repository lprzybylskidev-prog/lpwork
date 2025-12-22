<?php
declare(strict_types=1);

namespace LPwork\Queue\Driver;

use LPwork\Queue\Contract\JobSerializerInterface;
use LPwork\Queue\Contract\QueueDriverInterface;
use LPwork\Queue\QueueJob;

/**
 * Filesystem-backed queue driver (best for dev/test).
 */
class FilesystemQueueDriver implements QueueDriverInterface
{
    /**
     * @var string
     */
    private string $path;

    /**
     * @var JobSerializerInterface
     */
    private JobSerializerInterface $serializer;

    /**
     * @param string                 $path
     * @param JobSerializerInterface $serializer
     */
    public function __construct(string $path, JobSerializerInterface $serializer)
    {
        $this->path = rtrim($path, '/');
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function push(QueueJob $job): void
    {
        $queueDir = $this->queueDir($job->queue());
        if (!\is_dir($queueDir)) {
            \mkdir($queueDir, 0777, true);
        }

        $file = $queueDir . '/' . $job->id() . '.job';
        \file_put_contents($file, $this->serializer->serialize($job));
    }

    /**
     * @inheritDoc
     */
    public function pop(int $timeoutSeconds): ?QueueJob
    {
        $deadline = \time() + $timeoutSeconds;

        while (true) {
            $job = $this->popOnce();

            if ($job !== null) {
                return $job;
            }

            if ($timeoutSeconds === 0 || \time() >= $deadline) {
                return null;
            }

            \usleep(200_000);
        }
    }

    /**
     * @inheritDoc
     */
    public function ack(QueueJob $job): void
    {
        // Already removed during pop.
    }

    /**
     * @inheritDoc
     */
    public function reject(QueueJob $job, bool $requeue): void
    {
        if ($requeue) {
            $this->push($job);
        }
    }

    /**
     * @inheritDoc
     */
    public function purge(): void
    {
        if (!\is_dir($this->path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir()) {
                @\rmdir($fileinfo->getPathname());
            } else {
                @\unlink($fileinfo->getPathname());
            }
        }
    }

    /**
     * @return QueueJob|null
     */
    private function popOnce(): ?QueueJob
    {
        if (!\is_dir($this->path)) {
            return null;
        }

        $iterator = new \FilesystemIterator($this->path, \FilesystemIterator::SKIP_DOTS);
        $files = [];

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo instanceof \SplFileInfo || !$fileinfo->isDir()) {
                continue;
            }

            $queueIterator = new \FilesystemIterator(
                $fileinfo->getPathname(),
                \FilesystemIterator::SKIP_DOTS,
            );

            foreach ($queueIterator as $jobFile) {
                if ($jobFile instanceof \SplFileInfo && $jobFile->isFile()) {
                    $files[] = $jobFile->getPathname();
                }
            }
        }

        if ($files === []) {
            return null;
        }

        \sort($files);
        $file = $files[0];
        $contents = \file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        \unlink($file);

        return $this->serializer->deserialize($contents);
    }

    /**
     * @param string $queue
     *
     * @return string
     */
    private function queueDir(string $queue): string
    {
        return $this->path . '/' . $queue;
    }
}
