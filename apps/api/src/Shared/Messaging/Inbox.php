<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Write;
use Aws\DynamoDb\Marshaler;
use Psr\Clock\ClockInterface;

/**
 * The events that a subscription applied, so that an event delivered twice is applied once. Each event gets an item
 * in the table of the subscription's context. The item is written with what the reaction writes, all or nothing, on
 * the condition that it does not exist yet. A reaction that fails writes none. The table forgets the item 15 days
 * later. That is longer than an event may wait (6 hours in the worker, a day of EventBridge retries, 14 days in the
 * failures).
 *
 * PK `INBOX#<subscription>`, SK `<eventId>`, `receivedAt` the time it was applied, `expiresAt` the epoch at which
 * the table forgets it.
 */
final readonly class Inbox
{
    private const string PARTITION = 'INBOX#';
    private const string NEW_ITEM = 'attribute_not_exists(SK)';
    private const string KEPT_FOR = '+15 days';

    /**
     * @param array<string, string> $tables each context's table name
     */
    public function __construct(
        private array $tables,
        private DynamoUnitOfWork $unitOfWork,
        private Marshaler $marshaler,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Keeps the event in the subscription's inbox, within the open unit of work.
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
