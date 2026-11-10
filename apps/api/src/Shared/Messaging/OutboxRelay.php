<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\OutboxItem;
use Aws\DynamoDb\Marshaler;
use Bref\Context\Context;
use Bref\Event\DynamoDb\DynamoDbEvent;
use Bref\Event\DynamoDb\DynamoDbHandler;
use Bref\Event\DynamoDb\DynamoDbRecord;

/**
 * The function the stream of each table calls with its new items of outbox (its mapping filters them): it
 * publishes their events on the bus, and writes nothing. A failure fails the whole batch, which the stream gives
 * again: an event may be published twice, the reactions are idempotent.
 */
final class OutboxRelay extends DynamoDbHandler
{
    public function __construct(private readonly IntegrationEventBus $bus, private readonly Marshaler $marshaler)
    {
    }

    public function handleDynamoDb(DynamoDbEvent $event, Context $context): void
    {
        $this->bus->publish(...array_merge(...array_map($this->eventsOf(...), $event->getRecords())));
    }

    /**
     * @return list<TransportedEvent>
     */
    private function eventsOf(DynamoDbRecord $record): array
    {
        $item = $record->getNewImage();

        return null === $item ? [] : OutboxItem::fromDocument($this->marshaler->unmarshalItem($item))->events;
    }
}
