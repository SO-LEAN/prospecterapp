<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ExtendedParameterReflection;

/**
 * Reads, in each object a class creates, the texts written in each of its arguments, by the name of the parameter
 * they go to (Argument). A presenter creates its links resource this way, with the routes of the links it offers.
 *
 * @implements Collector<New_, list<array{caller: string, created: string, parameter: string, texts: list<string>}>>
 */
final readonly class ArgumentsCollector implements Collector
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $caller = $scope->getClassReflection();
        $created = $scope->getType($node)->getObjectClassReflections()[0] ?? null;
        if (null === $caller || null === $created || !$created->hasConstructor()) {
            return null;
        }
        $parameters = array_map(static fn (ExtendedParameterReflection $parameter): string => $parameter->getName(), $created->getConstructor()->getVariants()[0]->getParameters());
        $arguments = [];
        foreach ($node->getArgs() as $position => $argument) {
            $texts = self::textsIn($argument->value);
            $parameter = $argument->name?->toString() ?? $parameters[$position] ?? null;
            if ([] !== $texts && null !== $parameter) {
                $arguments[] = ['caller' => $caller->getName(), 'created' => $created->getName(), 'parameter' => $parameter, 'texts' => $texts];
            }
        }

        return [] === $arguments ? null : $arguments;
    }

    /**
     * The texts written in an expression, but in the objects it creates, which are read on their own.
     *
     * @return list<string>
     */
    private static function textsIn(Node $node): array
    {
        if ($node instanceof String_) {
            return [$node->value];
        }
        $texts = [];
        foreach ($node instanceof New_ ? [] : $node->getSubNodeNames() as $name) {
            foreach (self::nodesIn($node->{$name}) as $child) {
                $texts = [...$texts, ...self::textsIn($child)];
            }
        }

        return $texts;
    }

    /**
     * @return list<Node>
     */
    private static function nodesIn(mixed $subNode): array
    {
        $nodes = \is_array($subNode) ? $subNode : [$subNode];

        return array_values(array_filter($nodes, static fn (mixed $node): bool => $node instanceof Node));
    }
}
