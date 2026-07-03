<?php

declare(strict_types=1);

namespace LPWork\View;

use LPWork\Translation\Translator;
use LPWork\View\Contracts\ViewEngine;
use Throwable;

/**
 * Creates view factory instances from framework configuration.
 */
final class ViewFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $shared = [];

    /**
     * Creates a new ViewFactory instance.
     */
    public function __construct(
        private readonly ViewFinder $finder,
        private readonly ViewEngine $engine,
        private readonly ?Translator $translator = null,
        private readonly ?ViewDebugCollector $debugCollector = null,
    ) {}

    /**
     * @param array<string, mixed>|object $data
     */
    public function render(string $name, array|object $data = []): string
    {
        return $this->renderView($name, $data, allowLayout: true);
    }

    /**
     * @param array<string, mixed>|object $data
     */
    public function renderPartial(string $name, array|object $data = []): string
    {
        return $this->renderView($name, $data, allowLayout: false);
    }

    /**
     * Performs the share operation.
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function shared(): array
    {
        return $this->shared;
    }

    /**
     * @param array<string, mixed>|object $data
     */
    private function renderView(string $name, array|object $data, bool $allowLayout): string
    {
        return $this->renderPath($name, $this->finder->find($name), $data, $allowLayout);
    }

    /**
     * @param array<string, mixed>|object $data
     */
    private function renderPath(string $name, string $path, array|object $data, bool $allowLayout): string
    {
        $started = hrtime(true);
        $viewData = $this->mergeData($data);
        $context = new ViewRenderContext($this, $viewData, translator: $this->translator);

        try {
            $content = $this->engine->render($path, $viewData, $context);
            $layout = $context->layoutName();

            $this->recordDebugRender($name, $path, $layout, $viewData, $context, successful: true, started: $started);

            if (!$allowLayout || $layout === null) {
                return $content;
            }

            $layoutStarted = hrtime(true);
            $layoutData = $context->layoutData();
            $layoutPath = $this->finder->find($layout);
            $layoutContext = new ViewRenderContext(
                factory: $this,
                data: $layoutData,
                content: $content,
                sections: $context->sections(),
                translator: $this->translator,
            );
            $layoutContent = $this->engine->render($layoutPath, $layoutData, $layoutContext);
            $this->recordDebugRender($layout, $layoutPath, null, $layoutData, $layoutContext, successful: true, started: $layoutStarted);

            return $layoutContent;
        } catch (Throwable $throwable) {
            $this->recordDebugRender($name, $path, $context->layoutName(), $viewData, $context, successful: false, started: $started);

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed>|object $data
     * @return array<string, mixed>|object
     */
    private function mergeData(array|object $data): array|object
    {
        if (is_object($data)) {
            if ($this->shared === []) {
                return $data;
            }

            return [...$this->shared, 'data' => $data];
        }

        return [...$this->shared, ...$data];
    }

    /**
     * @param array<string, mixed>|object $data
     */
    private function recordDebugRender(
        string $name,
        string $path,
        ?string $layout,
        array|object $data,
        ViewRenderContext $context,
        bool $successful,
        int|float $started,
    ): void {
        $this->debugCollector?->record(
            name: $name,
            path: $path,
            layout: $layout,
            dataKeys: $this->dataKeys($data),
            sharedKeys: array_keys($this->shared),
            sections: array_keys($context->sections()),
            successful: $successful,
            durationMs: $this->durationMs($started),
            recordedAtMs: $this->epochMsForHrtime($started),
        );
    }

    private function durationMs(int|float $started): float
    {
        return round(max(0.0, hrtime(true) - $started) / 1_000_000, 3);
    }

    private function epochMsForHrtime(int|float $timestamp): float
    {
        $now = hrtime(true);

        return round((microtime(true) * 1000) - (($now - $timestamp) / 1_000_000), 3);
    }

    /**
     * @param array<string, mixed>|object $data
     * @return list<string>
     */
    private function dataKeys(array|object $data): array
    {
        if (is_object($data)) {
            return ['data'];
        }

        return array_keys($data);
    }
}
