<?php

declare(strict_types=1);

namespace PHPStan\Analyser {
    use PhpParser\Node\Name;

    class Scope
    {
        public function getFile(): string
        {
            return '';
        }

        public function resolveName(Name $name): string
        {
            return $name->toString();
        }
    }
}

namespace PHPStan\Rules {
    use PhpParser\Node;
    use PHPStan\Analyser\Scope;

    /**
     * @template TNode of Node
     */
    interface Rule
    {
        public function getNodeType(): string;

        /**
         * @return list<IdentifierRuleError>
         */
        public function processNode(Node $node, Scope $scope): array;
    }

    interface IdentifierRuleError
    {
    }

    final class RuleErrorBuilder
    {
        public static function message(string $message): self
        {
            return new self();
        }

        public function identifier(string $identifier): self
        {
            return $this;
        }

        public function build(): IdentifierRuleError
        {
            return new class implements IdentifierRuleError {
            };
        }
    }
}
