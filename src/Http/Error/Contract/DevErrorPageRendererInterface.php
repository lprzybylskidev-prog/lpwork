<?php
declare(strict_types=1);

namespace LPwork\Http\Error\Contract;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders developer-friendly error pages.
 */
interface DevErrorPageRendererInterface
{
    /**
     * Renders an HTML error page with diagnostic data.
     *
     * @param ServerRequestInterface $request
     * @param int                    $status
     * @param string                 $errorId
     * @param \Throwable             $throwable
     *
     * @return string
     */
    public function render(
        ServerRequestInterface $request,
        int $status,
        string $errorId,
        \Throwable $throwable,
        ?\LPwork\Http\Error\ErrorContext $context = null,
    ): string;
}
