<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Foundation\FrameworkModuleCatalog;
use LPWork\Foundation\FrameworkModuleDefinition;
use LPWork\Translation\Translator;

/**
 * Handles the about command console command.
 */
final readonly class AboutCommand implements Command
{
    /**
     * Creates a new AboutCommand instance.
     */
    public function __construct(
        private AboutRuntimeSnapshot $snapshot,
        private FrameworkModuleCatalog $modules,
        private CompiledCacheRegistry $caches,
        private Translator $translator,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'about';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Display runtime, cache, and framework module information.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->messages->title($output, 'LPWork application information');

        $this->messages->section($output, 'Application');
        $this->messages->summary($output, [
            'Name' => $this->snapshot->name(),
            'Base path' => $this->snapshot->basePath(),
            'Environment' => $this->snapshot->environment(),
            'Production' => $this->snapshot->production(),
            'Debug' => $this->snapshot->debug(),
            'Locale' => $this->snapshot->locale(),
        ]);

        $this->messages->section($output, 'Runtime');
        $this->messages->summary($output, [
            'PHP version' => $this->snapshot->phpVersion(),
            'PHP SAPI' => $this->snapshot->phpSapi(),
            'Operating system' => $this->snapshot->operatingSystem(),
            'Timezone' => $this->snapshot->timezone(),
            'Memory limit' => $this->snapshot->memoryLimit(),
            'Loaded extensions' => $this->snapshot->loadedExtensions(),
        ]);

        $this->messages->section($output, 'Configured services');
        $this->messages->summary($output, [
            'Cache driver' => $this->snapshot->cacheDriver(),
            'Storage disk' => $this->snapshot->storageDisk(),
            'Session driver' => $this->snapshot->sessionDriver(),
            'Queue connection' => $this->snapshot->queueConnection(),
            'Database connection' => $this->snapshot->databaseConnection(),
            'Mail transport' => $this->snapshot->mailTransport(),
            'Lock driver' => $this->snapshot->lockDriver(),
            'Lock store' => $this->snapshot->lockStore(),
            'Throttle storage' => $this->snapshot->throttleStorage(),
            'Broadcasting connection' => $this->snapshot->broadcastingConnection(),
            'Notification storage' => $this->snapshot->notificationDatabase(),
            'Scheduler history' => $this->snapshot->schedulerHistory(),
            'Observability reporters' => $this->snapshot->observabilityReporters(),
            'Maintenance store' => $this->snapshot->maintenanceStore(),
            'Security profile' => $this->snapshot->securityProfile(),
            'Security headers' => $this->snapshot->securityHeaders(),
            'CSRF protection' => $this->snapshot->csrfProtection(),
        ]);

        $this->messages->section($output, 'Compiled caches');
        $this->messages->summary($output, $this->compiledCacheStates());

        $this->messages->section($output, 'Framework');
        $this->messages->summary($output, [
            'Framework version' => $this->snapshot->frameworkVersion(),
            'Framework modules' => $this->modules->count(),
        ]);

        $this->messages->section($output, 'Framework modules');
        $this->messages->table(
            $output,
            ['Module', 'Description'],
            array_map(
                fn(FrameworkModuleDefinition $module): array => [
                    $this->translator->get($module->nameTranslationKey()),
                    $this->translator->get($module->descriptionTranslationKey()),
                ],
                $this->modules->all(),
            ),
        );

        return 0;
    }

    private function cacheState(bool $cached): string
    {
        return $cached ? 'cached' : 'not cached';
    }

    /**
     * @return array<string, string>
     */
    private function compiledCacheStates(): array
    {
        $states = [];

        foreach ($this->caches->all() as $cache) {
            $states[$cache->label()] = $this->cacheState($cache->exists());
        }

        return $states;
    }
}
