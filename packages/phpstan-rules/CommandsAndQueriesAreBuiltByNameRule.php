<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A command or a query of the core of more than three arguments is built with named arguments: built by position,
 * two arguments of the same type (an email and a phone number, both `?string`) are swapped without an error. A
 * command or a query is the class its `<Class>Handler` handles. The rule holds for the code of the applications,
 * not for their tests.
 *
 * @implements Rule<New_>
 */
final class CommandsAndQueriesAreBuiltByNameRule implements Rule
{
    private const int MAX_POSITIONAL_ARGUMENTS = 3;

    private const array HANDLERS = [
        'Prospecter\\Core\\SharedKernel\\Application\\CommandHandler',
        'Prospecter\\Core\\SharedKernel\\Application\\QueryHandler',
    ];

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name || $this->isATest($scope) || !$this->isBuiltByPosition($node->getArgs())) {
            return [];
        }

        $class = $scope->resolveName($node->class);

        if (!$this->isACommandOrAQuery($class)) {
            return [];
        }

        return [RuleErrorBuilder::message(\sprintf('%s has more than %d arguments: name each of them, so that two of the same type cannot be swapped (docs/architecture/clean-code.md).', $class, self::MAX_POSITIONAL_ARGUMENTS))->identifier('prospecter.builtByPosition')->build()];
    }

    private function isATest(Scope $scope): bool
    {
        return str_contains($scope->getFile(), '/tests/');
    }

    /**
     * @param list<Arg> $arguments
     */
    private function isBuiltByPosition(array $arguments): bool
    {
        return \count($arguments) > self::MAX_POSITIONAL_ARGUMENTS && array_any($arguments, static fn (Arg $argument): bool => null === $argument->name);
    }

    private function isACommandOrAQuery(string $class): bool
    {
        $handler = $class.'Handler';

        return $this->reflectionProvider->hasClass($handler)
            && array_any(self::HANDLERS, fn (string $interface): bool => $this->reflectionProvider->getClass($handler)->implementsInterface($interface));
    }
}
