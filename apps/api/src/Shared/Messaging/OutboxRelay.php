<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Persistence\OutboxItem;
use App\Shared\Typed;
use Aws\DynamoDb\Marshaler;
use Bref\Context\Context;
use Bref\Event\DynamoDb\DynamoDbEvent;
use Bref\Event\DynamoDb\DynamoDbHandler;
use Bref\Event\DynamoDb\DynamoDbRecord;

/**
 * The function that each table's stream calls with its new outbox items (its mapping filters them). It publishes
 * their events on the bus, and writes nothing. A failure fails the whole batch, and the stream sends the batch again.
 * So an event may be published twice: the reactions are idempotent.
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

        return null === $item ? [] : OutboxItem::fromDocument(Typed::document($this->marshaler->unmarshalItem($item)))->events;
    }
}
