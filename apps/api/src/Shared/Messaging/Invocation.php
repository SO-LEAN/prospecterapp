<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Typed;

/**
 * What a subscription's rule sends the worker, through its input transformer: the subscription's name, and the event
 * as it travelled, `{"subscription", "source", "detailType", "detail"}`.
 */
final readonly class Invocation
{
    private const array FIELDS = ['subscription', 'source', 'detailType', 'detail'];
    private const array FIELDS_OF_THE_DETAIL = ['eventId', 'occurredAt', 'correlationId', 'causationId', 'depth', 'payload'];

    public function __construct(public string $subscription, public TransportedEvent $event)
    {
    }

    /**
     * @throws UnreadableEvent
     */
    public static function of(mixed $payload): self
    {
        $payload = self::hasEvery(self::FIELDS, $payload) ? Typed::document($payload) : null;

        if (null === $payload || !self::hasEvery(self::FIELDS_OF_THE_DETAIL, $payload['detail'])) {
            throw UnreadableEvent::because(new \UnexpectedValueException(sprintf('an invocation has %s, its detail %s', implode(', ', self::FIELDS), implode(', ', self::FIELDS_OF_THE_DETAIL))));
        }

        try {
            return new self(Typed::string($payload['subscription']), TransportedEvent::fromEventBridge(['source' => $payload['source'], 'detail-type' => $payload['detailType'], 'detail' => $payload['detail']]));
        } catch (\UnexpectedValueException $failure) {
            throw UnreadableEvent::because($failure);
        }
    }

    /**
     * What identifies the event in the logs, read as far as the worker's input allows.
     *
     * @return array{subscription: mixed, eventId: mixed, correlationId: mixed}
     */
    public static function traceOf(mixed $payload): array
    {
        $payload = is_array($payload) ? $payload : [];
        $detail = is_array($payload['detail'] ?? null) ? $payload['detail'] : [];

        return [
            'subscription' => $payload['subscription'] ?? null,
            'eventId' => $detail['eventId'] ?? null,
            'correlationId' => $detail['correlationId'] ?? null,
        ];
    }

    /**
     * @param list<string> $fields
     */
    private static function hasEvery(array $fields, mixed $value): bool
    {
        return is_array($value) && array_all($fields, static fn (string $field): bool => array_key_exists($field, $value));
    }
}
