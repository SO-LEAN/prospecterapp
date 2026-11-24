<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Reads, in each method, the methods it calls on the types it calls them on, and the objects it creates (Call).
 *
 * @implements Collector<CallLike, list<array{caller: string, callerMethod: string, target: string, targetMethod: string, line: int}>>
 */
final readonly class CallsCollector implements Collector
{
    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $caller = $scope->getClassReflection();
        $method = $scope->getFunction();
        $targetMethod = self::targetMethodOf($node);
        if (null === $caller || null === $method || null === $targetMethod) {
            return null;
        }
        $calls = array_map(
            static fn (string $target): array => ['caller' => $caller->getName(), 'callerMethod' => $method->getName(), 'target' => $target, 'targetMethod' => $targetMethod, 'line' => $node->getStartLine()],
            self::targetsOf($node, $scope),
        );

        return [] === $calls ? null : $calls;
    }

    private static function targetMethodOf(CallLike $call): ?string
    {
        return match (true) {
            $call instanceof New_ => Call::CREATION,
            $call instanceof MethodCall, $call instanceof NullsafeMethodCall, $call instanceof StaticCall => $call->name instanceof Identifier ? $call->name->toString() : null,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private static function targetsOf(CallLike $call, Scope $scope): array
    {
        return match (true) {
            $call instanceof MethodCall, $call instanceof NullsafeMethodCall => array_values($scope->getType($call->var)->getObjectClassNames()),
            $call instanceof New_, $call instanceof StaticCall => $call->class instanceof Name ? [$scope->resolveName($call->class)] : [],
            default => [],
        };
    }
}
