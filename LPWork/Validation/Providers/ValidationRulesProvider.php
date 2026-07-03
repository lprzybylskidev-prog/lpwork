<?php

declare(strict_types=1);

namespace LPWork\Validation\Providers;

use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationRuleRegistry;

/**
 * Registers validation rules provider services with the framework container.
 */
abstract class ValidationRulesProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $registry = $container->make(ValidationRuleRegistry::class);

        if (!$registry instanceof ValidationRuleRegistry) {
            return;
        }

        foreach ($this->rules($container) as $rule) {
            $registry->register($rule);
        }
    }

    /**
     * @return list<class-string<ValidationRule>>
     */
    abstract protected function validationRules(): array;

    /**
     * @return list<ValidationRule>
     */
    private function rules(Container $container): array
    {
        $rules = [];

        foreach ($this->validationRules() as $rule) {
            $resolved = $container->make($rule);

            if ($resolved instanceof ValidationRule) {
                $rules[] = $resolved;
            }
        }

        return $rules;
    }
}
