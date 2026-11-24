<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Hands the facts its collectors read of the whole code to the event storming's error formatter, as the metadata of
 * a single error: an analysis of PHPStan reports nothing else.
 *
 * @implements Rule<CollectedDataNode>
 */
final readonly class EventStormingFactsRule implements Rule
{
    public const string IDENTIFIER = 'prospecter.eventStormingFacts';

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classesByFile = $node->get(ClassFactsCollector::class);
        $anAnalysedFile = array_key_first($classesByFile);
        if (null === $anAnalysedFile) {
            return [];
        }
        $classes = array_merge(...array_values($classesByFile));

        return [
            RuleErrorBuilder::message('The facts of the event storming')
                ->identifier(self::IDENTIFIER)
                ->file($anAnalysedFile)
                ->line(1)
                ->metadata(['classes' => $classes, 'calls' => self::callsIn($node), 'arguments' => self::argumentsIn($node)])
                ->build(),
        ];
    }

    /**
     * @return list<array{caller: string, callerMethod: string, target: string, targetMethod: string, conditional: bool}>
     */
    private static function callsIn(CollectedDataNode $node): array
    {
        $spans = self::conditionalSpansIn($node);
        $calls = [];
        foreach ($node->get(CallsCollector::class) as $callsOfAFile) {
            foreach (array_merge(...$callsOfAFile) as $call) {
                $conditional = array_any(
                    $spans[$call['caller'].'::'.$call['callerMethod']] ?? [],
                    static fn (array $span): bool => $call['line'] >= $span[0] && $call['line'] <= $span[1],
                );
                $calls[] = ['caller' => $call['caller'], 'callerMethod' => $call['callerMethod'], 'target' => $call['target'], 'targetMethod' => $call['targetMethod'], 'conditional' => $conditional];
            }
        }

        return $calls;
    }

    /**
     * @return list<array{caller: string, created: string, parameter: string, texts: list<string>}>
     */
    private static function argumentsIn(CollectedDataNode $node): array
    {
        $arguments = [];
        foreach ($node->get(ArgumentsCollector::class) as $creationsOfAFile) {
            $arguments = [...$arguments, ...array_merge(...$creationsOfAFile)];
        }

        return $arguments;
    }

    /**
     * @return array<string, list<array{int, int}>> the spans of lines of each method that run under a condition
     */
    private static function conditionalSpansIn(CollectedDataNode $node): array
    {
        $spans = [];
        foreach ($node->get(ConditionalLinesCollector::class) as $methodsOfAFile) {
            foreach ($methodsOfAFile as $method) {
                $spans[$method['class'].'::'.$method['method']] = $method['spans'];
            }
        }

        return $spans;
    }
}
