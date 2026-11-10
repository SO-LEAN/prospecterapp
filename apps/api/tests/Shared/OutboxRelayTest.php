<?php

namespace Tests\App\Shared;

use App\Shared\Messaging\OutboxRelay;
use App\Shared\Messaging\TransportedEvent;
use App\Shared\Persistence\OutboxItem;
use Aws\DynamoDb\Marshaler;
use Bref\Context\Context;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Tests\App\Double\InMemoryEventBridge;

/**
 * The relay publishes on the bus the events of the items of outbox the stream of a table gives it, ten at a time,
 * the most a PutEvents takes; a bus refusing some of them fails the whole batch, for the stream to give it again.
 */
class OutboxRelayTest extends KernelTestCase
{
    private OutboxRelay $relay;
    private InMemoryEventBridge $bus;

    protected function setUp(): void
    {
        $this->relay = self::getContainer()->get(OutboxRelay::class);
        $this->bus = self::getContainer()->get(InMemoryEventBridge::class);
    }

    /**
     * @param list<int> $eventsByItem
     * @param list<int> $entriesByPut
     */
    #[DataProvider('batches')]
    public function testTheRelayPublishesTheEventsOfABatchTenAtATime(array $eventsByItem, array $entriesByPut): void
    {
        $this->relay->handle($this->batchOf(...$eventsByItem), Context::fake());

        $this->assertSame($entriesByPut, array_map(count(...), $this->bus->puts));
    }

    /**
     * @return iterable<string, array{list<int>, list<int>}>
     */
    public static function batches(): iterable
    {
        yield 'one item of 23 events' => [[23], [10, 10, 3]];
        yield 'three items of 23 events' => [[9, 9, 5], [10, 10, 3]];
        yield 'one event' => [[1], [1]];
    }

    public function testAnEventIsPublishedAsItTravels(): void
    {
        $event = $this->event();

        $this->relay->handle($this->batchWith([$event]), Context::fake());

        $this->assertSame([[[
            'Source' => 'prospecter.prospecting.internal',
            'DetailType' => 'ProspectingStopped.v1',
            'Detail' => json_encode($event->toOutbox()['detail'], \JSON_THROW_ON_ERROR),
            'EventBusName' => 'prospecter-test-integration',
        ]]], $this->bus->puts);
    }

    public function testAPartialFailureOfTheBusFailsTheWholeBatch(): void
    {
        $this->bus->failEntriesOfTheNextPut(1);

        $this->expectExceptionMessageIsOrContains('The bus refused 1 of 10 events');

        $this->relay->handle($this->batchOf(15), Context::fake());
    }

    /**
     * @return array<string, mixed> what the stream of a table gives the relay: the insertion of each item
     */
    private function batchOf(int ...$eventsByItem): array
    {
        return $this->batchWith(...array_map(fn (int $count): array => array_map($this->event(...), range(1, $count)), $eventsByItem));
    }

    /**
     * @param list<TransportedEvent> ...$itemsOfEvents
     *
     * @return array<string, mixed>
     */
    private function batchWith(array ...$itemsOfEvents): array
    {
        $marshaler = new Marshaler();
        $records = array_map(static fn (array $events): array => [
            'eventName' => 'INSERT',
            'eventSource' => 'aws:dynamodb',
            'dynamodb' => [
                'Keys' => ['PK' => ['S' => 'OUTBOX#x'], 'SK' => ['S' => 'OUTBOX']],
                'NewImage' => $marshaler->marshalItem(new OutboxItem(Uuid::v7()->toRfc4122(), $events, new \DateTimeImmutable())->document()),
                'StreamViewType' => 'NEW_IMAGE',
            ],
        ], $itemsOfEvents);

        return ['Records' => $records];
    }

    private function event(): TransportedEvent
    {
        return new TransportedEvent('prospecter.prospecting.internal', 'ProspectingStopped.v1', Uuid::v7()->toRfc4122(), '2026-11-10T09:00:00.000000Z', Uuid::v7()->toRfc4122(), null, 0, ['organizationId' => Uuid::v7()->toRfc4122()]);
    }
}
