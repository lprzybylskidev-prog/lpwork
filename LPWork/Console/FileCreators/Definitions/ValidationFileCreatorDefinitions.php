<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the validation file creator definitions framework component.
 */
final readonly class ValidationFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'validation-rule',
                description: 'Create a validation rule.',
                defaultDirectory: 'App/Validation/Rules',
                suffix: 'Rule',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Validation\Contracts\ValidationRule;
                    use LPWork\Validation\ValidationMessage;

                    final readonly class {{ class }} implements ValidationRule
                    {
                        public function name(): string
                        {
                            return '{{ rule_name }}';
                        }

                        /**
                         * @param array<string, mixed> $input
                         * @param array<array-key, mixed> $parameters
                         */
                        public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
                        {
                            return null;
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/Validation/ValidationProvider.php', 'validationRules'),
            ),
            new FileCreatorDefinition(
                type: 'form-request',
                description: 'Create a form request.',
                defaultDirectory: 'App/Validation/Requests',
                suffix: 'Request',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Validation\FormRequest;

                    final class {{ class }} extends FormRequest
                    {
                        /**
                         * @return array<string, mixed>
                         */
                        public function rules(): array
                        {
                            return [];
                        }
                    }
                    PHP,
            ),
        ];
    }
}
