<?php

namespace App\Shared\Messaging;

/**
 * An event as it travels, in the shape of an event of EventBridge: from `prospecter.<context>` for an integration
 * event, `prospecter.<context>.internal` for a domain event; its detail-type the short name of its class and the
 * version of its contract; its detail the identity of this publication, its moment, its cause, and the event as
 * plain data.
 *
 * @phpstan-type Detail array{eventId: string, occurredAt: string, correlationId: string, causationId: ?string, depth: int, payload: array<string, mixed>}
 */
final readonly class TransportedEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $source,
        public string $detailType,
        public string $eventId,
        public string $occurredAt,
        public string $correlationId,
        public ?string $causationId,
        public int $depth,
        public array $payload,
    ) {
    }

    /**
     * @param array{source: string, detail-type: string, detail: Detail} $event
     */
    public static function fromEventBridge(array $event): self
    {
        return self::of($event['source'], $event['detail-type'], $event['detail']);
    }

    /**
     * @return array{source: string, detail-type: string, detail: Detail}
     */
    public function toEventBridge(): array
    {
        return ['source' => $this->source, 'detail-type' => $this->detailType, 'detail' => $this->detail()];
    }

    /**
     * @param array{source: string, detailType: string, detail: Detail} $entry
     */
    public static function fromOutbox(array $entry): self
    {
        return self::of($entry['source'], $entry['detailType'], $entry['detail']);
    }

    /**
     * @return array{source: string, detailType: string, detail: Detail} the entry of an item of outbox
     */
    public function toOutbox(): array
    {
        return ['source' => $this->source, 'detailType' => $this->detailType, 'detail' => $this->detail()];
    }

    /**
     * The bounded context the event happened in, as its source names it: `personal-data` for
     * `prospecter.personal-data.internal`.
     */
    public function context(): string
    {
        return explode('.', $this->source)[1];
    }

    /**
     * What a reaction to this event publishes is caused by it, one level deeper.
     */
    public function causeOfAReaction(): Cause
    {
        return new Cause($this->correlationId, $this->eventId, $this->depth + 1);
    }

    /**
     * The integration event translating a domain event for the other contexts is the same fact: caused by it, at
     * its depth.
     */
    public function causeOfATranslation(): Cause
    {
        return new Cause($this->correlationId, $this->eventId, $this->depth);
    }

    /**
     * @param Detail $detail
     */
    private static function of(string $source, string $detailType, array $detail): self
    {
        return new self($source, $detailType, $detail['eventId'], $detail['occurredAt'], $detail['correlationId'], $detail['causationId'], $detail['depth'], $detail['payload']);
    }

    /**
     * @return Detail
     */
    private function detail(): array
    {
        return [
            'eventId' => $this->eventId,
            'occurredAt' => $this->occurredAt,
            'correlationId' => $this->correlationId,
            'causationId' => $this->causationId,
            'depth' => $this->depth,
            'payload' => $this->payload,
        ];
    }
}
