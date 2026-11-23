<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;

/**
 * A parent event's handler also receives every child event added later. That suits a handler that publishes
 * facts, because the facts must follow anything that happens to their subject. A business reaction subscribes to
 * its events one by one, so that a new event never reaches it unless someone decides so.
 */
class ParentEventsFeedOnlyFactsTest extends TestCase
{
    private const string PUBLICATION_OF_FACTS = '/\\\\Publish\w+Facts\\\\/';

    public function testOnlyAPublicationOfFactsSubscribesToAParentEvent(): void
    {
        $this->assertSame([], $this->reactionsToAParentEvent());
    }

    /**
     * @return list<string> "handler: parent event" for every handler that subscribes to an abstract event and publishes no facts
     */
    private function reactionsToAParentEvent(): array
    {
        $reactions = [];

        foreach (EventHandlersOfTheCore::all() as $handler) {
            $event = $this->eventOf($handler);

            if ($this->isAParent($event) && !$this->publishesFacts($handler)) {
                $reactions[] = sprintf('%s: %s', $handler, $event);
            }
        }

        return $reactions;
    }

    /**
     * @param class-string<EventHandler> $handler
     */
    private function eventOf(string $handler): string
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : '';
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
