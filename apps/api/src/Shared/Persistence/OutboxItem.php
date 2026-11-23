<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\Messaging\TransportedEvent;
use App\Shared\Typed;

/**
 * What a commit published in a context. It is written with what the commit changed, in the context's table. The
 * table's stream gives the new item to the relay, which publishes its events on the bus. The table forgets the item
 * a week later, long after the relay published it.
 *
 * PK `OUTBOX#<commit>`, SK `OUTBOX`, `events` the list of the events as they travel (a JSON string), `occurredAt`
 * the commit's time, `expiresAt` the epoch at which the table forgets it.
 */
final readonly class OutboxItem
{
    public const string PARTITION = 'OUTBOX#';
    public const string SORT = 'OUTBOX';
    private const string KEPT_FOR = '+7 days';
    private const int MOST_BYTES_OF_AN_ITEM = 400 * 1024;

    /**
     * @param list<TransportedEvent> $events
     */
    public function __construct(public string $commitId, public array $events, public \DateTimeImmutable $occurredAt)
    {
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function fromDocument(array $document): self
    {
        return new self(
            substr(Typed::string($document['PK']), strlen(self::PARTITION)),
            array_values(array_map(TransportedEvent::fromOutbox(...), Typed::array(json_decode(Typed::string($document['events']), true, flags: \JSON_THROW_ON_ERROR)))),
            Documents::momentOf(Typed::string($document['occurredAt'])),
        );
    }

    /**
     * @return array{PK: string, SK: string, events: string, occurredAt: string, expiresAt: int}
     *
     * @throws \LogicException when its events exceed an item's size. A transaction is never split.
     */
    public function document(): array
    {
        $events = json_encode(array_map(static fn (TransportedEvent $event): array => $event->toOutbox(), $this->events), \JSON_THROW_ON_ERROR);

        if (strlen($events) > self::MOST_BYTES_OF_AN_ITEM) {
            throw new \LogicException(sprintf('The events of a commit fit in an item of %d bytes, not %d', self::MOST_BYTES_OF_AN_ITEM, strlen($events)));
        }

        return [
            'PK' => self::PARTITION.$this->commitId,
            'SK' => self::SORT,
            'events' => $events,
            'occurredAt' => Documents::moment($this->occurredAt),
            'expiresAt' => $this->occurredAt->modify(self::KEPT_FOR)->getTimestamp(),
        ];
    }
}
