<?php

declare(strict_types=1);

namespace LPWork\View;

use const EXTR_SKIP;

use function extract;

use LPWork\Filesystem\Filesystem;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\Exceptions\ViewNotFoundException;
use LPWork\View\Exceptions\ViewRenderException;

use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

use Throwable;

/**
 * Represents the php view engine framework component.
 */
final readonly class PhpViewEngine implements ViewEngine
{
    private PhpViewEngineExtensions $extensions;

    /**
     * Creates a new PhpViewEngine instance.
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
        ?PhpViewEngineExtensions $extensions = null,
    ) {
        $this->extensions = $extensions ?? new PhpViewEngineExtensions();
    }

    /**
     * @param array<string, mixed>|object $data
     */
    public function render(string $path, array|object $data, ViewRenderContext $context): string
    {
        if (!$this->filesystem->isFile($path)) {
            throw ViewNotFoundException::forName($path, [$path]);
        }

        return $this->evaluate($path, $data, $context);
    }

    /**
     * @param array<string, mixed>|object $data
     */
    private function evaluate(string $path, array|object $data, ViewRenderContext $context): string
    {
        $data = $this->dataWithExtensions($data);

        $render = static function (string $__path, array|object $__data, ViewRenderContext $view): string {
            ob_start();

            try {
                if (is_array($__data)) {
                    extract($__data, EXTR_SKIP);
                } else {
                    $data = $__data;
                }

                include $__path;

                $contents = ob_get_clean();
            } catch (Throwable $throwable) {
                ob_end_clean();

                throw $throwable;
            }

            if (!is_string($contents)) {
                throw ViewRenderException::failed($__path);
            }

            return $contents;
        };

        try {
            return $render($path, $data, $context);
        } catch (Throwable $throwable) {
            if ($throwable instanceof ViewRenderException || $throwable instanceof ViewNotFoundException) {
                throw $throwable;
            }

            throw ViewRenderException::failed($path, $throwable);
        }
    }

    /**
     * @param array<string, mixed>|object $data
     * @return array<string, mixed>|object
     */
    private function dataWithExtensions(array|object $data): array|object
    {
        $extensions = $this->extensions->variables();

        if ($extensions === []) {
            return $data;
        }

        if (is_array($data)) {
            return [...$extensions, ...$data];
        }

        return [...$extensions, 'data' => $data];
    }
}
