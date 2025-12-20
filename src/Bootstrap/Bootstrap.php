<?php
declare(strict_types=1);

namespace LPwork\Bootstrap;

use LPwork\Runtime\RuntimeType;

/**
 * Handles the initial bootstrapping of the LPwork framework.
 */
class Bootstrap
{
    /**
     * Boots the framework for the detected runtime context.
     *
     * @return void
     */
    public function run(): void
    {
        $runtimeType = $this->detectRuntimeType();
        // The actual kernel dispatch will be wired here in next iterations.
    }

    /**
     * Determines the runtime environment type.
     *
     * @return RuntimeType
     */
    private function detectRuntimeType(): RuntimeType
    {
        if (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return RuntimeType::Cli;
        }

        return RuntimeType::Http;
    }
}
