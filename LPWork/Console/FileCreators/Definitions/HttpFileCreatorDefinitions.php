<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the http file creator definitions framework component.
 */
final readonly class HttpFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'controller',
                description: 'Create an HTTP controller.',
                defaultDirectory: 'App/Controllers',
                suffix: 'Controller',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Responses\HttpResponse;

                    final readonly class {{ class }}
                    {
                        public function __invoke(): HttpResponse
                        {
                            return HttpResponse::html('{{ class }}');
                        }
                    }
                    PHP,
            ),
            new FileCreatorDefinition(
                type: 'middleware',
                description: 'Create HTTP middleware.',
                defaultDirectory: 'App/Middleware',
                suffix: 'Middleware',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use Closure;
                    use LPWork\Middleware\Contracts\Middleware;
                    use LPWork\Requests\HttpRequest;
                    use LPWork\Responses\Contracts\Response;

                    final readonly class {{ class }} implements Middleware
                    {
                        /**
                         * @param Closure(HttpRequest): Response $next
                         */
                        public function handle(HttpRequest $request, Closure $next): Response
                        {
                            return $next($request);
                        }
                    }
                    PHP,
            ),
            new FileCreatorDefinition(
                type: 'route-definition',
                description: 'Create a route definition class.',
                defaultDirectory: 'App/Routes',
                suffix: 'Routes',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Responses\HttpResponse;
                    use LPWork\Routing\Contracts\RouteDefinition;
                    use LPWork\Routing\Router;

                    final readonly class {{ class }} implements RouteDefinition
                    {
                        public function register(Router $router): void
                        {
                            $router
                                ->get('/{{ route_path }}', static fn(): HttpResponse => HttpResponse::html('{{ class }}'))
                                ->name('{{ route_name }}');
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/Routes/RoutesProvider.php', 'routeDefinitions'),
            ),
        ];
    }
}
