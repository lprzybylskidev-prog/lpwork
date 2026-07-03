<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Context;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Session\Exceptions\SessionNotAttachedException;

/**
 * Registers session debug context provider services with the framework container.
 */
final readonly class SessionDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        $request = $context->request();

        if ($request === null) {
            return [];
        }

        try {
            $session = $request->session();

            return [
                'Session' => [
                    'Attached' => true,
                    'Regeneration requested' => $session->regenerationRequested(),
                    'Invalidation requested' => $session->invalidationRequested(),
                    'Data' => $session->all(),
                ],
            ];
        } catch (SessionNotAttachedException) {
            return [
                'Session' => [
                    'Attached' => false,
                ],
            ];
        }
    }
}
