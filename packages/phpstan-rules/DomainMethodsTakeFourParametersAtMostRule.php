<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A public domain method takes four parameters at most (ADR 0191). Values that go together become an argument
 * object named in the domain's words. A constructor is exempt: a value object or an aggregate lists its fields there.
 *
 * @implements Rule<ClassMethod>
 */
final class DomainMethodsTakeFourParametersAtMostRule implements Rule
{
    private const string MESSAGE = 'A public method of the domain takes four parameters at most: group those that go together in an argument object (docs/architecture/clean-code.md).';

    private const int MAXIMUM = 4;

    private const string DOMAIN_MODEL = '/Domain/Model/';

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isOfTheDomainModel($scope) || !$this->isPublicApi($node) || \count($node->params) <= self::MAXIMUM) {
            return [];
        }

        return [RuleErrorBuilder::message(self::MESSAGE)->identifier('prospecter.parameters')->build()];
    }

    private function isOfTheDomainModel(Scope $scope): bool
    {
        return str_contains($scope->getFile(), self::DOMAIN_MODEL);
    }

    private function isPublicApi(ClassMethod $method): bool
    {
        return $method->isPublic() && '__construct' !== $method->name->toLowerString();
    }
}
