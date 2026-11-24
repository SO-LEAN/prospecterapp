<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassMethodNode;

/**
 * Reads, in each method, the lines that run only under a condition: a branch, a loop, a closure, the right side of
 * a logical operator, and whatever follows an early return.
 *
 * @implements Collector<InClassMethodNode, array{class: string, method: string, spans: list<array{int, int}>}>
 */
final readonly class ConditionalLinesCollector implements Collector
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $method = $node->getOriginalNode();
        $spans = [];
        foreach (new NodeFinder()->find($method->stmts ?? [], static fn (Node $node): bool => true) as $found) {
            $spans = [...$spans, ...self::conditionalPartsOf($found, $method->getEndLine())];
        }

        return [] === $spans ? null : ['class' => $node->getClassReflection()->getName(), 'method' => $method->name->toString(), 'spans' => $spans];
    }

    /**
     * @return list<array{int, int}>
     */
    private static function conditionalPartsOf(Node $node, int $endOfTheMethod): array
    {
        return match (true) {
            $node instanceof Stmt\If_ => [...self::spansOf([...$node->stmts, ...$node->elseifs, ...($node->else->stmts ?? [])]), ...self::afterAnEarlyReturn($node, $endOfTheMethod)],
            $node instanceof Stmt\Foreach_, $node instanceof Stmt\For_, $node instanceof Stmt\While_, $node instanceof Stmt\Do_, $node instanceof Stmt\Catch_ => self::spansOf($node->stmts),
            $node instanceof Expr\Ternary => self::spansOf(array_filter([$node->if, $node->else])),
            $node instanceof Expr\Match_ => self::spansOf(array_map(static fn (Node\MatchArm $arm): Expr => $arm->body, $node->arms)),
            $node instanceof Expr\BinaryOp\Coalesce, $node instanceof Expr\BinaryOp\BooleanAnd, $node instanceof Expr\BinaryOp\BooleanOr => self::spansOf([$node->right]),
            $node instanceof Expr\Closure, $node instanceof Expr\ArrowFunction => self::spansOf([$node]),
            default => [],
        };
    }

    /**
     * @return list<array{int, int}>
     */
    private static function afterAnEarlyReturn(Stmt\If_ $if, int $endOfTheMethod): array
    {
        $returns = new NodeFinder()->findFirstInstanceOf([...$if->stmts, ...($if->else->stmts ?? [])], Stmt\Return_::class);

        return null === $returns ? [] : [[$if->getEndLine() + 1, $endOfTheMethod]];
    }

    /**
     * @param array<Node> $nodes
     *
     * @return list<array{int, int}>
     */
    private static function spansOf(array $nodes): array
    {
        return array_values(array_map(static fn (Node $node): array => [$node->getStartLine(), $node->getEndLine()], $nodes));
    }
}
