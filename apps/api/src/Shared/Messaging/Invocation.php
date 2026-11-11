<?php

namespace App\Shared\Messaging;

/**
 * What the rule of a subscription gives the worker, by its input transformer: the name of the subscription, and
 * the event as it travelled, `{"subscription", "source", "detailType", "detail"}`.
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
        if (!self::hasEvery(self::FIELDS, $payload) || !self::hasEvery(self::FIELDS_OF_THE_DETAIL, $payload['detail'])) {
            throw UnreadableEvent::because(new \UnexpectedValueException(sprintf('an invocation has %s, its detail %s', implode(', ', self::FIELDS), implode(', ', self::FIELDS_OF_THE_DETAIL))));
        }

        try {
            return new self($payload['subscription'], TransportedEvent::fromEventBridge(['source' => $payload['source'], 'detail-type' => $payload['detailType'], 'detail' => $payload['detail']]));
        } catch (\TypeError $failure) {
            throw UnreadableEvent::because($failure);
        }
    }

    /**
     * What identifies the event in the logs, read as far as it can be from what the worker was given.
     *
     * @return array{subscription: mixed, eventId: mixed, correlationId: mixed}
     */
    public static function traceOf(mixed $payload): array
    {
        return [
            'subscription' => $payload['subscription'] ?? null,
            'eventId' => $payload['detail']['eventId'] ?? null,
            'correlationId' => $payload['detail']['correlationId'] ?? null,
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
