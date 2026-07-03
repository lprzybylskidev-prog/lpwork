<?php

declare(strict_types=1);

namespace LPWork\View;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

use function htmlspecialchars;
use function is_scalar;

use LPWork\Translation\Translator;
use LPWork\View\Contracts\ViewContext;
use LPWork\View\Exceptions\ViewRenderException;

use function ob_get_clean;
use function ob_start;

use Stringable;

/**
 * Provides helper methods and section state while rendering PHP views.
 */
final class ViewRenderContext implements ViewContext
{
    private ?string $layout = null;

    /**
     * @var array<string, mixed>|object
     */
    private array|object $layoutData = [];

    /**
     * @var array<string, string>
     */
    private array $sections;

    /**
     * @var list<string>
     */
    private array $sectionStack = [];

    /**
     * @param array<string, mixed>|object $data
     * @param array<string, string> $sections
     */
    public function __construct(
        private readonly ViewFactory $factory,
        private readonly array|object $data,
        private string $content = '',
        array $sections = [],
        private readonly ?Translator $translator = null,
    ) {
        $this->sections = $sections;
    }

    /**
     * Escapes a scalar or stringable value for safe HTML output.
     */
    public function e(mixed $value): string
    {
        if (!is_scalar($value) && !$value instanceof Stringable) {
            throw ViewRenderException::valueCannotBeEscaped();
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Translates a keyed message with optional replacement parameters.
     *
     * @param array<string, scalar|Stringable|null> $parameters Placeholder values keyed by placeholder name.
     */
    public function t(string $key, array $parameters = [], ?string $locale = null): string
    {
        return $this->translator?->get($key, $parameters, $locale) ?? $key;
    }

    /**
     * Translates an inline text string with optional replacement parameters.
     *
     * @param array<string, scalar|Stringable|null> $parameters Placeholder values keyed by placeholder name.
     */
    public function text(string $text, array $parameters = [], ?string $locale = null): string
    {
        return $this->translator?->text($text, $parameters, $locale) ?? $text;
    }

    /**
     * Renders another view with data merged into the current view data.
     *
     * @param array<string, mixed>|object $data Data passed to the partial view.
     */
    public function partial(string $name, array|object $data = []): string
    {
        return $this->factory->renderPartial($name, $this->mergeData($data));
    }

    /**
     * Renders and immediately echoes another view.
     *
     * @param array<string, mixed>|object $data Data passed to the included view.
     */
    public function include(string $name, array|object $data = []): void
    {
        echo $this->partial($name, $data);
    }

    /**
     * Selects the layout view that should wrap the current view output.
     *
     * @param array<string, mixed>|object $data Data passed to the layout view.
     */
    public function layout(string $name, array|object $data = []): void
    {
        $this->layout = $name;
        $this->layoutData = $this->mergeData($data);
    }

    /**
     * Starts capturing a named section.
     */
    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    /**
     * Stops capturing the current section and stores its contents.
     */
    public function end(): void
    {
        $name = array_pop($this->sectionStack);

        if ($name === null) {
            throw ViewRenderException::sectionNotStarted('');
        }

        $contents = ob_get_clean();

        if (!is_string($contents)) {
            throw ViewRenderException::sectionNotStarted($name);
        }

        $this->sections[$name] = $contents;
    }

    /**
     * Returns a rendered section or a default value when the section is absent.
     */
    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Returns the current view content passed into a layout.
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Returns the selected layout view name, if one was selected.
     */
    public function layoutName(): ?string
    {
        return $this->layout;
    }

    /**
     * Returns data that should be passed to the selected layout.
     *
     * @return array<string, mixed>|object
     */
    public function layoutData(): array|object
    {
        return $this->layoutData;
    }

    /**
     * Returns all captured sections by section name.
     *
     * @return array<string, string>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * @param array<string, mixed>|object $data
     * @return array<string, mixed>|object
     */
    private function mergeData(array|object $data): array|object
    {
        if (is_object($data)) {
            return $data;
        }

        if (is_array($this->data)) {
            return [...$this->data, ...$data];
        }

        if ($data === []) {
            return $this->data;
        }

        return ['data' => $this->data, ...$data];
    }
}
