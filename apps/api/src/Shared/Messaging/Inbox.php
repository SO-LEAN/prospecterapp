<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Write;
use Aws\DynamoDb\Marshaler;
use Psr\Clock\ClockInterface;

/**
 * The events a subscription applied, so that an event delivered twice is applied once: an item per event, in the
 * table of the context of the subscription, written with what the reaction writes, all or nothing, under the
 * condition that it is not there yet; a reaction that fails writes none. The table forgets it 15 days later, longer
 * than an event may wait (6 hours in the worker, a day of retries of EventBridge, 14 days in the failures).
 *
 * PK `INBOX#<subscription>`, SK `<eventId>`, `receivedAt` the moment it was applied, `expiresAt` the epoch the table
 * forgets it at.
 */
final readonly class Inbox
{
    private const string PARTITION = 'INBOX#';
    private const string NEW_ITEM = 'attribute_not_exists(SK)';
    private const string KEPT_FOR = '+15 days';

    /**
     * @param array<string, string> $tables the name of the table of each context
     */
    public function __construct(
        private array $tables,
        private DynamoUnitOfWork $unitOfWork,
        private Marshaler $marshaler,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Keeps the event in the inbox of the subscription, in the open unit of work.
     */
    public function receive(Subscription $subscription, TransportedEvent $event): void
    {
        $table = $this->tables[$subscription->context] ?? throw new \LogicException(sprintf('No table keeps the inbox of the context %s', $subscription->context));
        $now = $this->clock->now();
        $item = [
            'PK' => self::PARTITION.$subscription->name,
            'SK' => $event->eventId,
            'receivedAt' => Documents::moment($now),
            'expiresAt' => $now->modify(self::KEPT_FOR)->getTimestamp(),
        ];

        $this->unitOfWork->write(new Write(
            $table,
            $item['PK'],
            $item['SK'],
            ['Put' => ['TableName' => $table, 'Item' => $this->marshaler->marshalItem($item), 'ConditionExpression' => self::NEW_ITEM]],
            static fn (ConditionFailed $cancelled): AlreadyApplied => AlreadyApplied::by($subscription->name, $event->eventId, $cancelled),
        ));
    }
}
