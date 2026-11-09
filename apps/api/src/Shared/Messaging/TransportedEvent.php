<?php

namespace App\Shared\Messaging;

/**
 * An event as it travels, in the shape of an event of EventBridge: from `prospecter.<context>` for an integration
 * event, `prospecter.<context>.internal` for a domain event; its detail-type the short name of its class and the
 * version of its contract; its detail the identity of this publication, its moment, its cause, and the event as
 * plain data.
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
     * @param array{source: string, detail-type: string, detail: array{eventId: string, occurredAt: string, correlationId: string, causationId: ?string, depth: int, payload: array<string, mixed>}} $event
     */
    public static function fromEventBridge(array $event): self
    {
        $detail = $event['detail'];

        return new self($event['source'], $event['detail-type'], $detail['eventId'], $detail['occurredAt'], $detail['correlationId'], $detail['causationId'], $detail['depth'], $detail['payload']);
    }

    /**
     * @return array{source: string, detail-type: string, detail: array{eventId: string, occurredAt: string, correlationId: string, causationId: ?string, depth: int, payload: array<string, mixed>}}
     */
    public function toEventBridge(): array
    {
        return [
            'source' => $this->source,
            'detail-type' => $this->detailType,
            'detail' => [
                'eventId' => $this->eventId,
                'occurredAt' => $this->occurredAt,
                'correlationId' => $this->correlationId,
                'causationId' => $this->causationId,
                'depth' => $this->depth,
                'payload' => $this->payload,
            ],
        ];
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
}
