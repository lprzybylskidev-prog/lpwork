<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\View\ViewFactory;

/**
 * Represents the view health check framework component.
 */
final readonly class ViewHealthCheck implements HealthCheck
{
    /**
     * Creates a new ViewHealthCheck instance.
     */
    public function __construct(
        private ViewFactory $views,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'views';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $this->views->renderPartial('lpwork::health-check', ['name' => 'views']);

        return HealthCheckResult::healthy($this->name(), 'View factory renders the framework welcome view.');
    }
}
