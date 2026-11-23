<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Typed;

/**
 * An event as it travels, in the shape of an EventBridge event. Its source is `prospecter.<context>` for an
 * integration event, and `prospecter.<context>.internal` for a domain event. Its detail-type is the class's short
 * name and the contract's version. Its detail holds this publication's id, its time, its cause, and the event as
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
     * An EventBridge event, untyped as JSON decodes it.
     */
    public static function fromEventBridge(mixed $event): self
    {
        $event = Typed::document($event);

        return self::of($event['source'] ?? null, $event['detail-type'] ?? null, $event['detail'] ?? null);
    }

    /**
     * @return array{source: string, detail-type: string, detail: Detail}
     */
    public function toEventBridge(): array
    {
        return ['source' => $this->source, 'detail-type' => $this->detailType, 'detail' => $this->detail()];
    }

    /**
     * An outbox item's entry, untyped as JSON decodes it.
     */
    public static function fromOutbox(mixed $entry): self
    {
        $entry = Typed::document($entry);

        return self::of($entry['source'] ?? null, $entry['detailType'] ?? null, $entry['detail'] ?? null);
    }

    /**
     * @return array{source: string, detailType: string, detail: Detail} an outbox item's entry
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
     * The cause of what a reaction to this event publishes: this event, one level deeper.
     */
    public function causeOfAReaction(): Cause
    {
        return new Cause($this->correlationId, $this->eventId, $this->depth + 1);
    }

    /**
     * The integration event that translates a domain event for the other contexts is the same fact. Its cause is the
     * domain event, at the same depth.
     */
    public function causeOfATranslation(): Cause
    {
        return new Cause($this->correlationId, $this->eventId, $this->depth);
    }

    /**
     * @throws \UnexpectedValueException when a field is missing or of another type
     */
    private static function of(mixed $source, mixed $detailType, mixed $detail): self
    {
        $detail = Typed::document($detail);

        return new self(
            Typed::string($source),
            Typed::string($detailType),
            Typed::string($detail['eventId'] ?? null),
            Typed::string($detail['occurredAt'] ?? null),
            Typed::string($detail['correlationId'] ?? null),
            Typed::stringOrNull($detail['causationId'] ?? null),
            Typed::int($detail['depth'] ?? null),
            Typed::document($detail['payload'] ?? null),
        );
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
