<?php

declare(strict_types=1);

namespace Tests\support\phpstan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Tests\support\architecture\GlobalUsageBoundaries;

/**
 * @implements Rule<FuncCall>
 */
final readonly class DirectGlobalFunctionUsageRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $function = strtolower($node->name->toString());

        if (!in_array($function, GlobalUsageBoundaries::restrictedFunctions(), true)) {
            return [];
        }

        if (GlobalUsageBoundaries::canUseFunction($scope->getFile(), $function)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Direct global function %s() is forbidden here. Use an explicit framework boundary class.',
                $function,
            ))
                ->identifier('lpwork.directGlobalFunctionUsage')
                ->build(),
        ];
    }

}
