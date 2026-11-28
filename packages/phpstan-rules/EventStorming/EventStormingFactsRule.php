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
                ->metadata(['classes' => $classes, 'calls' => self::callsIn($node)])
                ->build(),
        ];
    }

    /**
     * @return list<array{caller: string, callerMethod: string, target: string, targetMethod: string, conditional: bool, arm: array{choice: string, index: int}|null}>
     */
    private static function callsIn(CollectedDataNode $node): array
    {
        $conditionalLines = self::conditionalLinesIn($node);
        $calls = [];
        foreach ($node->get(CallsCollector::class) as $callsOfAFile) {
            foreach (array_merge(...$callsOfAFile) as $call) {
                $method = $call['caller'].'::'.$call['callerMethod'];
                $within = static fn (array $span): bool => $call['line'] >= $span[0] && $call['line'] <= $span[1];
                $underALoneCondition = array_any($conditionalLines[$method]['spans'] ?? [], $within);
                $arms = array_values(array_filter($conditionalLines[$method]['arms'] ?? [], $within));
                $calls[] = [
                    'caller' => $call['caller'],
                    'callerMethod' => $call['callerMethod'],
                    'target' => $call['target'],
                    'targetMethod' => $call['targetMethod'],
                    'conditional' => $underALoneCondition || [] !== $arms,
                    'arm' => !$underALoneCondition && 1 === \count($arms) ? ['choice' => $method.'#'.$arms[0][2], 'index' => $arms[0][3]] : null,
                ];
            }
        }

        return $calls;
    }

    /**
     * @return array<string, array{spans: list<array{int, int}>, arms: list<array{int, int, int, int}>}> the lines of each
     *                                                                                                   method that run under a condition
     */
    private static function conditionalLinesIn(CollectedDataNode $node): array
    {
        $lines = [];
        foreach ($node->get(ConditionalLinesCollector::class) as $methodsOfAFile) {
            foreach ($methodsOfAFile as $method) {
                $lines[$method['class'].'::'.$method['method']] = ['spans' => $method['spans'], 'arms' => $method['arms']];
            }
        }

        return $lines;
    }
}
