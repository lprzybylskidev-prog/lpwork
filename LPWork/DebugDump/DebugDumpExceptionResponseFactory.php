<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\DebugDump\Exceptions\DumpAndDieException;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Creates debug dump exception response factory instances from framework configuration.
 */
final readonly class DebugDumpExceptionResponseFactory
{
    /**
     * Creates a new DebugDumpExceptionResponseFactory instance.
     */
    public function __construct(
        private DebugDumpRenderer $renderer,
        private ?DebugBarPageRenderer $debugBar = null,
    ) {}

    /**
     * Builds or returns make.
     */
    public function make(Throwable $throwable): ?HttpResponse
    {
        if (!$throwable instanceof DumpAndDieException) {
            return null;
        }

        return HttpResponse::html(
            $this->renderer->page($throwable->record(), $this->debugBar?->renderCurrent() ?? ''),
            500,
            ['X-LPWork-Debug-Dump' => $throwable->record()->id()],
        );
    }
}
