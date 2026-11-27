<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A class asks the queries of one context at most (ADR 0223). An anticorruption adapter asks one context. Only a
 * `*Composition` joins the answers of several. A query is a class that its `<Class>Handler` handles as a
 * `QueryHandler`, or the question of a published service: a class of the core beside its `<Class>Service`. Its context
 * is the namespace that follows `Prospecter\Core\`. The rule applies to the code, not to its tests.
 *
 * @implements Rule<InClassNode>
 */
final class QueriesOfOneContextRule implements Rule
{
    private const string CORE = 'Prospecter\\Core\\';

    private const string COMPOSITION = 'Composition';

    /**
     * Records a contact and their organization in one gesture of an AI: it reads what exists in Prospecting and
     * PersonalData, then runs their commands (ADR 0177).
     */
    private const string ORCHESTRATES_COMMANDS = 'App\\Identification\\ContactRecorder';

    private const string QUERY_HANDLER ='Prospecter\\Core\\SharedKernel\\Application\\Handler\\QueryHandler';

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection()->getName();
        if (self::isATest($scope) || self::mayJoinContexts($class)) {
            return [];
        }

        $contexts = $this->contextsAskedBy($node);
        if (\count($contexts) < 2) {
            return [];
        }

        return [RuleErrorBuilder::message(\sprintf('%s asks queries of %s: only a *Composition joins several contexts, and an anticorruption adapter asks one (ADR 0223).', $class, implode(', ', $contexts)))->identifier('prospecter.queriesOfOneContext')->build()];
    }

    private static function isATest(Scope $scope): bool
    {
        return str_contains($scope->getFile(), '/tests/');
    }

    private static function mayJoinContexts(string $class): bool
    {
        return str_ends_with($class, self::COMPOSITION) || self::ORCHESTRATES_COMMANDS === $class;
    }

    /**
     * @return list<string> the contexts of the queries the class builds, in alphabetical order
     */
    private function contextsAskedBy(InClassNode $node): array
    {
        $built = array_map(static fn (New_ $new): string => $new->class instanceof Name ? $new->class->toString() : '', (new NodeFinder())->findInstanceOf($node->getOriginalNode()->stmts, New_::class));
        $contexts = array_values(array_unique(array_map(self::contextOf(...), array_filter($built, $this->isAQuery(...)))));
        sort($contexts);

        return $contexts;
    }

    private function isAQuery(string $class): bool
    {
        return $this->isHandledAsAQuery($class) || $this->isAPublishedQuestion($class);
    }

    private function isHandledAsAQuery(string $class): bool
    {
        $handler = $class.'Handler';

        return $this->reflectionProvider->hasClass($handler) && $this->reflectionProvider->getClass($handler)->implementsInterface(self::QUERY_HANDLER);
    }

    private function isAPublishedQuestion(string $class): bool
    {
        return str_starts_with($class, self::CORE) && $this->reflectionProvider->hasClass($class.'Service');
    }

    private static function contextOf(string $class): string
    {
        return explode('\\', substr($class, \strlen(self::CORE)))[0];
    }
}
