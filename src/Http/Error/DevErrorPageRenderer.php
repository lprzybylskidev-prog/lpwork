<?php
declare(strict_types=1);

namespace LPwork\Http\Error;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Http\Error\Contract\DevErrorPageRendererInterface;
use LPwork\Http\Error\ErrorContext;
use LPwork\Http\Middleware\SessionMiddleware;
use LPwork\Http\Session\Contract\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\TemplateHelper;

/**
 * Default Whoops-based renderer with curated data tables.
 */
final class DevErrorPageRenderer implements DevErrorPageRendererInterface
{
    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $config;

    /**
     * @var string|null
     */
    private ?string $envDetailsTemplate = null;

    /**
     * @param ConfigRepositoryInterface $config
     */
    public function __construct(ConfigRepositoryInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function render(
        ServerRequestInterface $request,
        int $status,
        string $errorId,
        \Throwable $throwable,
        ?ErrorContext $context = null,
    ): string {
        try {
            $tables =
                $context !== null
                    ? $this->buildTablesFromContext($context)
                    : $this->buildTables($request, $errorId);
            $envTemplatePath = $this->envDetailsTemplate();

            $handler = new class ($tables, $envTemplatePath) extends PrettyPageHandler {
                /**
                 * @var array<string, array<string, mixed>>
                 */
                private array $tables;

                /**
                 * @var string
                 */
                private string $envDetailsTemplate;

                /**
                 * @param array<string, array<string, mixed>> $tables
                 * @param string                              $envDetailsTemplate
                 */
                public function __construct(array $tables, string $envDetailsTemplate)
                {
                    parent::__construct();
                    $this->tables = $tables;
                    $this->envDetailsTemplate = $envDetailsTemplate;
                }

                /**
                 * @param string|null $label
                 *
                 * @return array[]|callable
                 */
                public function getDataTables($label = null)
                {
                    if ($label !== null) {
                        return $this->tables[$label] ?? [];
                    }

                    return $this->tables;
                }

                /**
                 * Override to use only custom tables (no default superglobals).
                 *
                 * @return int
                 */
                public function handle()
                {
                    $templateFile = $this->getResource('views/layout.html.php');
                    $cssFile = $this->getResource('css/whoops.base.css');
                    $zeptoFile = $this->getResource('js/zepto.min.js');
                    $prismJs = $this->getResource('js/prism.js');
                    $prismCss = $this->getResource('css/prism.css');
                    $clipboard = $this->getResource('js/clipboard.min.js');
                    $jsFile = $this->getResource('js/whoops.base.js');

                    $inspector = $this->getInspector();
                    $frames = $this->getExceptionFrames();
                    $code = $this->getExceptionCode();

                    $vars = [
                        'page_title' => $this->getPageTitle(),
                        'stylesheet' => file_get_contents($cssFile),
                        'zepto' => file_get_contents($zeptoFile),
                        'prismJs' => file_get_contents($prismJs),
                        'prismCss' => file_get_contents($prismCss),
                        'clipboard' => file_get_contents($clipboard),
                        'javascript' => file_get_contents($jsFile),
                        'header' => $this->getResource('views/header.html.php'),
                        'header_outer' => $this->getResource('views/header_outer.html.php'),
                        'frame_list' => $this->getResource('views/frame_list.html.php'),
                        'frames_description' => $this->getResource(
                            'views/frames_description.html.php',
                        ),
                        'frames_container' => $this->getResource('views/frames_container.html.php'),
                        'panel_details' => $this->getResource('views/panel_details.html.php'),
                        'panel_details_outer' => $this->getResource(
                            'views/panel_details_outer.html.php',
                        ),
                        'panel_left' => $this->getResource('views/panel_left.html.php'),
                        'panel_left_outer' => $this->getResource('views/panel_left_outer.html.php'),
                        'frame_code' => $this->getResource('views/frame_code.html.php'),
                        'env_details' => $this->envDetailsTemplate,
                        'title' => $this->getPageTitle(),
                        'name' => explode('\\', $inspector->getExceptionName()),
                        'message' => $inspector->getExceptionMessage(),
                        'previousMessages' => $inspector->getPreviousExceptionMessages(),
                        'docref_url' => $inspector->getExceptionDocrefUrl(),
                        'code' => $code,
                        'previousCodes' => $inspector->getPreviousExceptionCodes(),
                        'plain_exception' => \Whoops\Exception\Formatter::formatExceptionPlain(
                            $inspector,
                        ),
                        'frames' => $frames,
                        'has_frames' => !!count($frames),
                        'handler' => $this,
                        'handlers' => [],
                        'active_frames_tab' =>
                            count($frames) && $frames->offsetGet(0)->isApplication()
                                ? 'application'
                                : 'all',
                        'has_frames_tabs' => $this->getApplicationPaths(),
                        'tables' => $this->getDataTables(),
                        'preface' => '',
                    ];

                    $templateHelper = new TemplateHelper();
                    $templateHelper->setVariables($vars);
                    $templateHelper->render($templateFile);

                    return self::QUIT;
                }
            };

            $handler->setPageTitle('LPwork Error');

            $run = new Run();
            $run->allowQuit(false);
            $run->writeToOutput(false);
            $run->pushHandler($handler);

            $html = $run->handleException($throwable);

            if (!\is_string($html)) {
                $html = '';
            }

            return $html;
        } catch (\Throwable $e) {
            $fallback = $throwable ?? $e;
            $safeErrorId = $errorId ?? 'unknown';
            $escaped = \htmlspecialchars(
                $fallback->getMessage(),
                \ENT_QUOTES | \ENT_SUBSTITUTE,
                'UTF-8',
            );

            return "<h1>Application error</h1><p>{$escaped}</p><p>Error ID: {$safeErrorId}</p>";
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $errorId
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildTables(ServerRequestInterface $request, string $errorId): array
    {
        $session = $this->extractSession($request);
        $appConfig = $this->config->get('app', []);

        return [
            'APP' => $appConfig,
            'REQUEST' => [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_string' => $request->getUri()->getQuery(),
                'query_params' => $request->getQueryParams(),
                'parsed_body' => $request->getParsedBody(),
                'files' => $request->getUploadedFiles(),
                'headers' => $request->getHeaders(),
                'cookies' => $request->getCookieParams(),
                'error_id' => $errorId,
            ],
            'SESSION' => $this->sessionData($session),
            'ENV' => $_ENV,
        ];
    }

    /**
     * @param ErrorContext $context
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildTablesFromContext(ErrorContext $context): array
    {
        $request = $context->request();
        $request['error_id'] = $context->id();
        $route = [];

        if (isset($request['route'])) {
            $route = (array) $request['route'];
            unset($request['route']);
        }

        $tables = [
            'APP' => $context->app(),
            'ROUTE' => $route !== [] ? $route : ['info' => 'no route context'],
            'REQUEST' => $request,
            'SESSION' => $context->session(),
            'ENV' => $context->env(),
        ];

        return $tables;
    }

    /**
     * @param SessionInterface|null $session
     *
     * @return array<string, mixed>
     */
    private function sessionData(?SessionInterface $session): array
    {
        if ($session === null) {
            return ['info' => 'no session'];
        }

        return [
            'id' => $session->id(),
            'data' => $session->all(),
        ];
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface|null
     */
    private function extractSession(ServerRequestInterface $request): ?SessionInterface
    {
        $session = $request->getAttribute(SessionMiddleware::ATTRIBUTE);

        if ($session instanceof SessionInterface) {
            return $session;
        }

        return null;
    }

    /**
     * @return string
     */
    private function envDetailsTemplate(): string
    {
        if ($this->envDetailsTemplate !== null) {
            return $this->envDetailsTemplate;
        }

        $template = <<<'PHP'
        <?php
        /** @var array<string, array<string, mixed>> $tables */
        /** @var \Whoops\Util\TemplateHelper $tpl */
        ?>
        <div class="details">
          <h2 class="details-heading">Environment &amp; details:</h2>

          <div class="data-table-container" id="data-tables">
            <?php foreach ($tables as $label => $data): ?>
              <div class="data-table" id="sg-<?php echo $tpl->escape($tpl->slug($label)) ?>">
                <?php if (!empty($data)): ?>
                    <label><?php echo $tpl->escape($label) ?></label>
                    <table class="data-table">
                      <thead>
                        <tr>
                          <td class="data-table-k">Key</td>
                          <td class="data-table-v">Value</td>
                        </tr>
                      </thead>
                    <?php foreach ($data as $k => $value): ?>
                      <tr>
                        <td><?php echo $tpl->escape($k) ?></td>
                        <td><?php echo $tpl->dump($value) ?></td>
                      </tr>
                    <?php endforeach ?>
                    </table>
                <?php else: ?>
                    <label class="empty"><?php echo $tpl->escape($label) ?></label>
                    <span class="empty">empty</span>
                <?php endif ?>
              </div>
            <?php endforeach ?>
          </div>
        </div>
        PHP;

        $path = \sys_get_temp_dir() . '/lpwork_env_details.php';
        \file_put_contents($path, $template);

        $this->envDetailsTemplate = $path;

        return $this->envDetailsTemplate;
    }
}
