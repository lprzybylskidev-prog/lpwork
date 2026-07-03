<?php

declare(strict_types=1);

use LPWork\View\ViewRenderContext;

if (!isset($view) || !$view instanceof ViewRenderContext) {
    throw new RuntimeException('Health check view requires a view render context.');
}

if (!isset($name) || !is_string($name)) {
    throw new RuntimeException('Health check view requires a string name.');
}
?>
<?= $view->e($name) ?>
