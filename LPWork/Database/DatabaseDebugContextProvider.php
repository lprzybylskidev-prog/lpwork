<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers database debug context provider services with the framework container.
 */
final readonly class DatabaseDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new DatabaseDebugContextProvider instance.
     */
    public function __construct(
        private DatabaseDebugCollector $collector,
        private bool $appDebug,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        $queries = [];

        foreach ($this->collector->recent() as $execution) {
            $query = [
                'Connection' => $execution->connection,
                'SQL' => $execution->sql,
                'Duration ms' => $execution->durationMs,
                'Successful' => $execution->successful,
            ];

            if ($this->appDebug) {
                $query['Bindings'] = $execution->bindings;
            } else {
                $query['Bindings count'] = count($execution->bindings);
            }

            if ($execution->exception !== null) {
                $query['Exception'] = $execution->exception::class;
            }

            $queries[] = $query;
        }

        return [
            'Database' => [
                'Queries' => $queries,
            ],
        ];
    }
}
