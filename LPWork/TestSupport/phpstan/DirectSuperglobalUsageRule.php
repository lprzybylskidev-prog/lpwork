<?php

declare(strict_types=1);

namespace Tests\support\phpstan;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Tests\support\architecture\GlobalUsageBoundaries;

/**
 * @implements Rule<Variable>
 */
final readonly class DirectSuperglobalUsageRule implements Rule
{
    public function getNodeType(): string
    {
        return Variable::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!is_string($node->name)) {
            return [];
        }

        if (!in_array($node->name, GlobalUsageBoundaries::superglobals(), true)) {
            return [];
        }

        if (GlobalUsageBoundaries::canUseSuperglobal($scope->getFile(), $node->name)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Direct superglobal $%s usage is forbidden here. Wrap global input in an explicit request/session boundary.',
                $node->name,
            ))
                ->identifier('lpwork.directSuperglobalUsage')
                ->build(),
        ];
    }

}
