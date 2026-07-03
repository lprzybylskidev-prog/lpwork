<?php

declare(strict_types=1);

namespace LPWork\Health\Controllers;

use LPWork\Health\HealthRunner;
use LPWork\Responses\HttpResponse;

/**
 * Handles health check controller HTTP requests.
 */
final readonly class HealthCheckController
{
    /**
     * Creates a new HealthCheckController instance.
     */
    public function __construct(
        private HealthRunner $health,
    ) {}

    /**
     * Performs the show operation.
     */
    public function show(): HttpResponse
    {
        $report = $this->health->run();

        return HttpResponse::json($report->toArray(), $report->statusCode());
    }
}
