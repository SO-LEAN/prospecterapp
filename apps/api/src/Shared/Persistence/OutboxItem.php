<?php

namespace App\Shared\Persistence;

use App\Shared\Messaging\TransportedEvent;

/**
 * What a commit published in a context, written with what it changed, in the table of the context: the stream of
 * the table gives the new item to the relay, which publishes its events on the bus. The table forgets it a week
 * later, long after the relay published it.
 *
 * PK `OUTBOX#<commit>`, SK `OUTBOX`, `events` the list of the events as they travel (a JSON string), `occurredAt`
 * the moment of the commit, `expiresAt` the epoch the table forgets it at.
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
            substr((string) $document['PK'], strlen(self::PARTITION)),
            array_map(TransportedEvent::fromOutbox(...), json_decode((string) $document['events'], true, flags: \JSON_THROW_ON_ERROR)),
            Documents::momentOf((string) $document['occurredAt']),
        );
    }

    /**
     * @return array{PK: string, SK: string, events: string, occurredAt: string, expiresAt: int}
     *
     * @throws \LogicException when its events are over the size of an item: a transaction is never split
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
