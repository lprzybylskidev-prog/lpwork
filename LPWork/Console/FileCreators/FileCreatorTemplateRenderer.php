<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

use function preg_replace;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Renders file creator template renderer output.
 */
final readonly class FileCreatorTemplateRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(FileCreatorDefinition $definition, ResolvedFile $file): string
    {
        $replacements = [
            'namespace' => $file->namespace(),
            'class' => $file->className(),
            'command_name' => $this->commandName($file->className()),
            'rule_name' => $this->kebabName($file->className(), 'Rule'),
            'config_key' => $this->kebabName($file->className(), 'Config'),
            'notification_subject' => $this->sentenceName($file->className(), 'Notification'),
            'broadcast_name' => $this->kebabName($file->className(), 'Event'),
            'channel_name' => $this->channelName($file->className()),
            'health_name' => $this->kebabName($file->className(), 'HealthCheck'),
            'route_name' => $this->kebabName($file->className(), 'Routes'),
            'route_path' => $this->kebabName($file->className(), 'Routes'),
            'table_name' => $this->tableName($file->className()),
            ...$definition->replacements(),
        ];

        $content = $definition->template();

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }

        return $content . "\n";
    }

    private function commandName(string $className): string
    {
        return $this->kebabName($className, 'Command');
    }

    private function kebabName(string $className, string $suffix): string
    {
        if ($suffix !== '' && str_ends_with($className, $suffix)) {
            $className = substr($className, 0, -strlen($suffix));
        }

        $name = preg_replace('/(?<!^)[A-Z]/', '-$0', $className) ?? $className;

        return strtolower(trim($name, '-'));
    }

    private function sentenceName(string $className, string $suffix): string
    {
        return str_replace('-', ' ', $this->kebabName($className, $suffix));
    }

    private function channelName(string $className): string
    {
        if (str_ends_with($className, 'Provider')) {
            return $this->kebabName($className, 'Provider');
        }

        return $this->kebabName($className, 'Event');
    }

    private function tableName(string $className): string
    {
        if (str_starts_with($className, 'Create') && str_ends_with($className, 'Table')) {
            $tableName = substr($className, 6, -5);

            if ($tableName !== '') {
                return $this->snakeName($tableName);
            }
        }

        return $this->snakeName($className);
    }

    private function snakeName(string $className): string
    {
        return str_replace('-', '_', $this->kebabName($className, ''));
    }
}
