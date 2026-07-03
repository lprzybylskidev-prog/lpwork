<?php

declare(strict_types=1);

namespace Tests\support\phpstan;

use LPWork\Environment\Environment;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Tests\support\architecture\EnvironmentUsageBoundaries;

/**
 * @implements Rule<StaticCall>
 */
final class EnvironmentUsageRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if ($scope->resolveName($node->class) !== Environment::class) {
            return [];
        }

        $methodName = $node->name instanceof Identifier ? $node->name->toString() : null;

        if ($methodName !== null && EnvironmentUsageBoundaries::canUseEnvironment($scope->getFile(), $methodName)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Environment can be used only in App/Shared/Configs. Bootstrap may call only Environment::init(). Use Config outside configuration files.',
            )
                ->identifier('lpwork.environmentUsage')
                ->build(),
        ];
    }

}
