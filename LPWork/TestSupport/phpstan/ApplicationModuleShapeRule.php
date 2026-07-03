<?php

declare(strict_types=1);

namespace Tests\support\phpstan;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Tests\support\architecture\ApplicationModuleShapeScanner;

/**
 * @implements Rule<ClassLike>
 */
final readonly class ApplicationModuleShapeRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (ApplicationModuleShapeScanner::isAllowedAppPhpFile($scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Application-owned PHP classes must live under App/Modules/{Module}, App/Shared, or App/AppServiceProvider.php.',
            )
                ->identifier('lpwork.applicationModuleShape')
                ->build(),
        ];
    }
}
