<?php

namespace Tests\App\Shared\Messaging;

use App\Shared\Identities;
use App\Shared\Messaging\Causation;
use App\Shared\Messaging\Delivery;
use App\Shared\Messaging\DomainEventTypes;
use App\Shared\Messaging\EventCodec;
use App\Shared\Messaging\EventLoop;
use App\Shared\Messaging\IntegrationEventTypes;
use App\Shared\Messaging\Payloads;
use App\Shared\Messaging\RoutingEvents;
use App\Shared\Messaging\Subscriptions;
use App\Shared\Messaging\SynchronousDelivery;
use App\Shared\Messaging\TransportedEvent;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Contract\Event\ContactsLeftWithoutOrganization;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * The reactions of the subscriptions are called at once, each event encoded then decoded as on the bus; an event
 * published by a reaction carries the cause it reacted to, one level deeper, and a loop stops beyond
 * MAX_EVENT_DEPTH.
 */
class SynchronousDeliveryTest extends TestCase
{
    private const string ID = '0199f1d2-6a3b-7c4d-8e5f-0123456789ab';

    private Causation $causation;
    private Recording $delivered;
    /** @var array<string, EventHandler> */
    private array $reactions = [];

    protected function setUp(): void
    {
        $this->causation = new Causation(new Identities());
    }

    public function testTwoReactionsAnsweringEachOtherStopBeyondTheMaximumDepth(): void
    {
        $events = $this->events([
            'prospecting-answer-collected' => ['prospecter.personal-data', 'ContactCollected.v1'],
            'prospecting-answer-forgotten' => ['prospecter.personal-data', 'ContactForgotten.v1'],
        ]);
        $this->reactions['prospecting-answer-collected'] = new Publishing($events, $this->forgotten(...));
        $this->reactions['prospecting-answer-forgotten'] = new Publishing($events, $this->collected(...));

        try {
            $this->causation->withinACommand(fn () => $events->publish($this->collected()));
            $this->fail('the loop went on');
        } catch (EventLoop) {
            $this->assertSame(EventLoop::MAX_EVENT_DEPTH + 1, array_last($this->delivered->events)?->depth);
        }
    }

    public function testAnEventPublishedByAReactionInheritsItsCause(): void
    {
        $events = $this->events([
            'prospecting-answer-collected' => ['prospecter.personal-data', 'ContactCollected.v1'],
            'prospecting-answer-forgotten' => ['prospecter.personal-data', 'ContactForgotten.v1'],
        ]);
        $this->reactions['prospecting-answer-collected'] = new Publishing($events, $this->forgotten(...));
        $this->reactions['prospecting-answer-forgotten'] = new Publishing($events, $this->leftWithoutOrganization(...));

        $this->causation->withinACommand(fn () => $events->publish($this->collected()));

        [$command, $ofDepthOne, $ofDepthTwo] = $this->delivered->events;
        $this->assertSame([0, 1, 2], [$command->depth, $ofDepthOne->depth, $ofDepthTwo->depth]);
        $this->assertNull($command->causationId);
        $this->assertSame($ofDepthOne->eventId, $ofDepthTwo->causationId);
        $this->assertSame($command->correlationId, $ofDepthTwo->correlationId);
    }

    public function testTheEventsOfOneCommandShareItsCorrelation(): void
    {
        $events = $this->events([]);

        $this->causation->withinACommand(fn () => $events->publish($this->collected(), $this->forgotten()));
        $this->causation->withinACommand(fn () => $events->publish($this->collected()));

        [$first, $second, $ofAnotherCommand] = $this->delivered->events;
        $this->assertSame($first->correlationId, $second->correlationId);
        $this->assertNotSame($first->correlationId, $ofAnotherCommand->correlationId);
        $this->assertSame([0, 0, 0], [$first->depth, $second->depth, $ofAnotherCommand->depth]);
    }

    /**
     * @param array<string, array{string, string}> $subscriptions the source and the detail-type, by name
     */
    private function events(array $subscriptions): Events
    {
        $codec = new EventCodec(new DomainEventTypes(), new IntegrationEventTypes(), new Payloads(TypeResolver::create()), new Identities());
        $locator = new ServiceLocator(array_map(fn (string $name): \Closure => fn (): EventHandler => $this->reactions[$name], array_combine(array_keys($subscriptions), array_keys($subscriptions))));
        $declared = array_map(static fn (array $subscription): array => ['context' => 'prospecting', 'sources' => [$subscription[0]], 'events' => [$subscription[1]], 'inbox' => true], $subscriptions);
        $this->delivered = new Recording(new SynchronousDelivery(new Subscriptions($locator, $declared), $codec, $this->causation));

        return new RoutingEvents(new MessageBus(), $this->delivered, $codec, $this->causation);
    }

    private function collected(): ContactCollected
    {
        return new ContactCollected(self::ID, self::ID, self::ID, [self::ID], new \DateTimeImmutable());
    }

    private function forgotten(): ContactForgotten
    {
        return new ContactForgotten(self::ID, self::ID, self::ID, new \DateTimeImmutable());
    }

    private function leftWithoutOrganization(): ContactsLeftWithoutOrganization
    {
        return new ContactsLeftWithoutOrganization(self::ID, self::ID, self::ID, [], new \DateTimeImmutable());
    }
}

/**
 * A reaction publishing an event of its own.
 */
final readonly class Publishing implements EventHandler
{
    /**
     * @param \Closure(): object $event
     */
    public function __construct(private Events $events, private \Closure $event)
    {
    }

    public function __invoke(object $received): void
    {
        $this->events->publish(($this->event)());
    }
}

/**
 * Keeps what goes to the delivery, then delivers it.
 */
final class Recording implements Delivery
{
    /** @var list<TransportedEvent> */
    public private(set) array $events = [];

    public function __construct(private readonly Delivery $delivery)
    {
    }

    public function deliver(TransportedEvent $event): void
    {
        $this->events[] = $event;
        $this->delivery->deliver($event);
    }
}
