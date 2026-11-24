<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * A parent event's handler also receives every child event added later. That suits a handler that publishes
 * facts, because the facts must follow anything that happens to their subject. A policy names its events one by one,
 * so that a new event never reaches it unless someone decides so (ADR 0203).
 */
class ParentEventsFeedOnlyFactsTest extends TestCase
{
    private const string PUBLICATION_OF_FACTS = '/\\\\Publish\w+Facts\\\\/';

    public function testOnlyAPublicationOfFactsSubscribesToAParentEvent(): void
    {
        $this->assertSame([], $this->reactionsToAParentEvent(array_filter(EventHandlersOfTheCore::all(), fn (string $handler): bool => !$this->publishesFacts($handler))));
    }

    public function testAPolicyTakesNoParentEvent(): void
    {
        $this->assertSame([], $this->reactionsToAParentEvent(PoliciesOfTheCore::all()));
    }

    /**
     * @param array<class-string> $reactions
     *
     * @return list<string> "reaction: parent event" for every reaction that takes an abstract event
     */
    private function reactionsToAParentEvent(array $reactions): array
    {
        $parents = [];

        foreach ($reactions as $reaction) {
            foreach ($this->eventsOf($reaction) as $event) {
                if ($this->isAParent($event)) {
                    $parents[] = sprintf('%s: %s', $reaction, $event);
                }
            }
        }

        return $parents;
    }

    /**
     * @param class-string $reaction
     *
     * @return list<string> the events its __invoke() takes, one or a union
     */
    private function eventsOf(string $reaction): array
    {
        $type = new \ReflectionMethod($reaction, '__invoke')->getParameters()[0]->getType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        return array_values(array_map(static fn (?\ReflectionType $type): string => $type instanceof \ReflectionNamedType ? $type->getName() : '', $types));
    }

    private function publishesFacts(string $handler): bool
    {
        return 1 === preg_match(self::PUBLICATION_OF_FACTS, $handler);
    }

    private function isAParent(string $event): bool
    {
        return class_exists($event) && new \ReflectionClass($event)->isAbstract();
    }
}
