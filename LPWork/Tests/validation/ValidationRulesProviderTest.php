<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Validation\Providers\ValidationRulesProvider;
use LPWork\Validation\Providers\ValidationServiceProvider;
use LPWork\Validation\ValidationRuleRegistry;
use Tests\support\validation\AlwaysFailsRule;

it('registers declared validation rule classes in the rule registry', function (): void {
    $container = new Container();
    new ValidationServiceProvider()->register($container);

    $provider = new class extends ValidationRulesProvider {
        /**
         * @return list<class-string<\LPWork\Validation\Contracts\ValidationRule>>
         */
        protected function validationRules(): array
        {
            return [
                AlwaysFailsRule::class,
            ];
        }
    };

    $provider->register($container);

    $registry = $container->make(ValidationRuleRegistry::class);

    expect($registry)->toBeInstanceOf(ValidationRuleRegistry::class);

    if ($registry instanceof ValidationRuleRegistry) {
        expect($registry->has('fail'))->toBeTrue();
    }
});
