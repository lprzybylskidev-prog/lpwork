<?php

declare(strict_types=1);

use LPWork\Frontend\ApplicationAssetRenderer;
use LPWork\Frontend\FrameworkAssets;
use LPWork\View\Contracts\ViewContext;

/** @var ViewContext $view */
/** @var ApplicationAssetRenderer $assets */
/** @var string $frameworkVersion */
/** @var int $moduleCount */
/** @var list<\LPWork\Foundation\FrameworkModuleDefinition> $modules */

?>
<!doctype html>
<html lang="<?= $view->e($view->t('welcome::lang')) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $view->e($view->t('welcome::title')) ?></title>
  <?= FrameworkAssets::faviconLink() ?>
  <?= FrameworkAssets::stylesheetElement() ?>
  <?= $assets->entry('welcome::app') ?>
</head>
<body class="lp-ui-body">
  <main class="lp-ui-welcome-shell">
    <section class="lp-ui-welcome-orbit" aria-labelledby="welcome-heading">
      <div class="lp-ui-welcome-orbit-copy">
        <div class="lp-ui-welcome-header">
          <?= FrameworkAssets::brand($view->t('welcome::brand'), 'lp-ui-framework-brand lp-ui-welcome-mark') ?>
          <span class="lp-ui-chip"><?= $view->e($view->t('welcome::version.label', ['version' => $frameworkVersion])) ?></span>
        </div>
        <p class="lp-ui-kicker"><?= $view->e($view->t('welcome::kicker')) ?></p>
        <h1 id="welcome-heading"><?= $view->e($view->t('welcome::heading')) ?></h1>
        <p><?= $view->e($view->t('welcome::lede')) ?></p>
      </div>

      <div class="lp-ui-welcome-orbit-map" aria-label="<?= $view->e($view->t('welcome::map.label')) ?>">
        <div class="lp-ui-welcome-core">
          <span>LP</span>
          <strong><?= $view->e((string) $moduleCount) ?></strong>
        </div>
        <span class="lp-ui-orbit-node is-http"><?= $view->e($view->t('welcome::map.http')) ?></span>
        <span class="lp-ui-orbit-node is-cli"><?= $view->e($view->t('welcome::map.cli')) ?></span>
        <span class="lp-ui-orbit-node is-data"><?= $view->e($view->t('welcome::map.data')) ?></span>
        <span class="lp-ui-orbit-node is-work"><?= $view->e($view->t('welcome::map.work')) ?></span>
        <span class="lp-ui-orbit-node is-debug"><?= $view->e($view->t('welcome::map.debug')) ?></span>
      </div>
    </section>

    <section class="lp-ui-capability-index" aria-labelledby="features-heading">
      <div class="lp-ui-index-heading">
        <div>
          <p class="lp-ui-kicker"><?= $view->e($view->t('welcome::features.kicker')) ?></p>
          <h2 id="features-heading"><?= $view->e($view->t('welcome::features.heading')) ?></h2>
          <span><?= $view->e($view->t('welcome::features.lede')) ?></span>
        </div>
        <div class="lp-ui-chip-row">
          <span class="lp-ui-chip"><?= $view->e($view->t('welcome::features.count', ['count' => $moduleCount])) ?></span>
        </div>
      </div>

      <ul class="lp-ui-capability-list">
        <?php foreach ($modules as $module): ?>
          <li>
            <strong><?= $view->e($view->t($module->nameTranslationKey())) ?></strong>
            <span><?= $view->e($view->t($module->descriptionTranslationKey())) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </main>
</body>
</html>
