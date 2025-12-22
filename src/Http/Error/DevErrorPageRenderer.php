<?php
declare(strict_types=1);

namespace LPwork\Http\Error;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Http\Error\Contract\DevErrorPageRendererInterface;
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
    ): string {
        try {
            $tables = $this->buildTables($request, $errorId);

            $handler = new class ($tables) extends PrettyPageHandler {
                /**
                 * @var array<string, array<string, mixed>>
                 */
                private array $tables;

                /**
                 * @param array<string, array<string, mixed>> $tables
                 */
                public function __construct(array $tables)
                {
                    parent::__construct();
                    $this->tables = $tables;
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
                        'env_details' => __DIR__ . '/templates/env_details.html.php',
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
}
