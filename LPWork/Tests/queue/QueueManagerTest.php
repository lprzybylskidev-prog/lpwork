<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Queue\QueueDriverFactory;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\QueueManager;
use Tests\support\queue\MutableClock;
use Tests\support\queue\RecordingJob;

it('dispatches jobs immediately through the sync driver', function (): void {
    $path = sys_get_temp_dir() . '/lpwork_queue_sync_' . uniqid('', true);
    $container = new Container();
    $clock = new MutableClock();
    $manager = new QueueManager(
        config: [
            'default' => 'sync',
            'queue' => 'default',
            'retry' => [
                'max_attempts' => 3,
                'retry_after_seconds' => 90,
                'delay_seconds' => 5,
            ],
            'retention' => [
                'completed_seconds' => 60,
                'failed_seconds' => 60,
            ],
            'connections' => [
                'sync' => [
                    'driver' => 'sync',
                ],
            ],
        ],
        driverFactory: new QueueDriverFactory(new QueueJobRunner($container), $clock),
        clock: $clock,
    );

    try {
        $id = $manager->dispatch(new RecordingJob($path, 'ran'));

        expect($id)->toHaveLength(32)
            ->and(file_get_contents($path))->toBe("ran\n");
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});
