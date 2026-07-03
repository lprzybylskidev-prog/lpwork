<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Controllers;

use LPWork\Maintenance\MaintenanceMode;
use LPWork\Maintenance\MaintenancePageRenderer;
use LPWork\Maintenance\MaintenanceState;
use LPWork\Responses\HttpResponse;

/**
 * Handles maintenance page controller HTTP requests.
 */
final readonly class MaintenancePageController
{
    /**
     * Creates a new MaintenancePageController instance.
     */
    public function __construct(
        private MaintenanceMode $maintenance,
        private MaintenancePageRenderer $renderer,
    ) {}

    /**
     * Performs the show operation.
     */
    public function show(): HttpResponse
    {
        $state = $this->maintenance->state();

        return $this->renderer->render($state->isActive() ? $state : MaintenanceState::active());
    }
}
