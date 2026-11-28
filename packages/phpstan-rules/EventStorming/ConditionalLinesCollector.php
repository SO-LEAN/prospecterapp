<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassMethodNode;

/**
 * Reads, in each method, the lines that run only under a condition. A lone condition: a branch without an
 * alternative, a loop, a closure, the right side of a logical operator, and whatever follows an early return. An
 * exclusive choice: the arms of a match, and the branches of an if or a ternary that has an else.
 *
 * @implements Collector<InClassMethodNode, array{class: string, method: string, spans: list<array{int, int}>, arms: list<array{int, int, int, int}>}>
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
        $nodes = new NodeFinder()->find($method->stmts ?? [], static fn (Node $node): bool => true);
        $spans = [];
        foreach ($nodes as $found) {
            $spans = [...$spans, ...self::conditionalPartsOf($found, $method->getEndLine())];
        }
        $arms = self::armsIn($nodes);

        return [] === $spans && [] === $arms ? null : ['class' => $node->getClassReflection()->getName(), 'method' => $method->name->toString(), 'spans' => $spans, 'arms' => $arms];
    }

    /**
     * @return list<array{int, int}>
     */
    private static function conditionalPartsOf(Node $node, int $endOfTheMethod): array
    {
        return match (true) {
            $node instanceof If_ => [...(null === $node->else ? self::spansOf([...$node->stmts, ...$node->elseifs]) : []), ...self::afterAnEarlyReturn($node, $endOfTheMethod)],
            $node instanceof Foreach_, $node instanceof For_, $node instanceof While_, $node instanceof Do_, $node instanceof Catch_ => self::spansOf($node->stmts),
            $node instanceof Ternary => null === $node->if ? self::spansOf([$node->else]) : [],
            $node instanceof Coalesce, $node instanceof BooleanAnd, $node instanceof BooleanOr => self::spansOf([$node->right]),
            $node instanceof Closure, $node instanceof ArrowFunction => self::spansOf([$node]),
            default => [],
        };
    }

    /**
     * @param array<Node> $nodes
     *
     * @return list<array{int, int, int, int}> each arm's first and last lines, its choice's rank in the method, and its own
     *                                         rank in its choice
     */
    private static function armsIn(array $nodes): array
    {
        $arms = [];
        $choices = array_values(array_filter(array_map(self::armSpansOf(...), $nodes)));
        foreach ($choices as $choice => $spans) {
            foreach (array_filter(array_values($spans)) as $index => $span) {
                $arms[] = [$span[0], $span[1], $choice, $index];
            }
        }

        return $arms;
    }

    /**
     * @return array<array{int, int}|null> the lines of each arm of an exclusive choice, null for an empty arm
     */
    private static function armSpansOf(Node $node): array
    {
        return match (true) {
            $node instanceof If_ && null !== $node->else => [self::spanOf($node->stmts), ...array_map(static fn (ElseIf_ $elseif): ?array => self::spanOf([$elseif]), $node->elseifs), self::spanOf($node->else->stmts)],
            $node instanceof Match_ => array_map(static fn (MatchArm $arm): ?array => self::spanOf([$arm->body]), $node->arms),
            $node instanceof Ternary && null !== $node->if => [self::spanOf([$node->if]), self::spanOf([$node->else])],
            default => [],
        };
    }

    /**
     * @return list<array{int, int}>
     */
    private static function afterAnEarlyReturn(If_ $if, int $endOfTheMethod): array
    {
        $returns = new NodeFinder()->findFirstInstanceOf([...$if->stmts, ...($if->else->stmts ?? [])], Return_::class);

        return null === $returns ? [] : [[$if->getEndLine() + 1, $endOfTheMethod]];
    }

    /**
     * @param array<Node> $nodes
     *
     * @return array{int, int}|null
     */
    private static function spanOf(array $nodes): ?array
    {
        $first = reset($nodes);
        $last = end($nodes);

        return false === $first || false === $last ? null : [$first->getStartLine(), $last->getEndLine()];
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
