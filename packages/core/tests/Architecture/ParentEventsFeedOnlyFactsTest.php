<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Application\PushUpcomingExchanges\PushUpcomingExchangesHandler;
use Prospecter\Core\Connectors\Application\ReadMailboxHistory\ReadMailboxHistoryHandler;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * A handler of a parent event also receives every child added to it later. That suits a publication of
 * facts, which must follow anything that happens to its subject. A business reaction subscribes to the
 * events it reacts to, one by one, so that a new event never reaches it unless someone decides so.
 */
class ParentEventsFeedOnlyFactsTest extends TestCase
{
    private const string PUBLICATION_OF_FACTS = '/\\\\Publish\w+Facts\\\\/';

    /**
     * The reactions allowed to subscribe to a parent. Each of these parents names a condition, and an event
     * meets it by extending the parent. Backlog #273 turns the reactions into translations of an event into a
     * command, and removes these parents with this list.
     */
    private const array REACTIONS_TO_A_CONDITION = [
        // ADR 0133: the calendar lacks the exchanges to come
        PushUpcomingExchangesHandler::class,
        // ADR 0154: the mailbox may hold messages not read yet
        ReadMailboxHistoryHandler::class,
    ];

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

            if ($this->isAParent($event) && !$this->publishesFacts($handler) && !in_array($handler, self::REACTIONS_TO_A_CONDITION, true)) {
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
