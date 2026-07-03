<?php

declare(strict_types=1);

namespace LPWork\Console;

use function fclose;
use function is_resource;

use LPWork\Console\Contracts\ProcessRunner;

use function proc_close;
use function proc_get_status;
use function proc_open;
use function stream_get_contents;
use function stream_set_blocking;
use function usleep;

/**
 * Represents the native process runner framework component.
 */
final class NativeProcessRunner implements ProcessRunner
{
    /**
     * Runs run.
     */
    public function run(ProcessCommand $command, Output $output): int
    {
        $process = proc_open(
            $command->command(),
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $command->workingDirectory(),
            $command->environment(),
        );

        if (!is_resource($process)) {
            $output->error('Unable to start process.');

            return 1;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        do {
            $this->flush($pipes[1], $output, error: false);
            $this->flush($pipes[2], $output, error: true);

            $status = proc_get_status($process);

            if ($status['running']) {
                usleep(10_000);
            }
        } while ($status['running']);

        $this->flush($pipes[1], $output, error: false);
        $this->flush($pipes[2], $output, error: true);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    /**
     * @param resource $stream
     */
    private function flush(mixed $stream, Output $output, bool $error): void
    {
        $content = stream_get_contents($stream);

        if ($content === false || $content === '') {
            return;
        }

        if ($error) {
            $output->writeError($content);

            return;
        }

        $output->write($content);
    }
}
