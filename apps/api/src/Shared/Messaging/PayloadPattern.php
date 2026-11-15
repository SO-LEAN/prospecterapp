<?php

namespace App\Shared\Messaging;

/**
 * The payloads a subscription listens to (`payload` in `config/subscriptions.json`), a pattern of EventBridge on the
 * payload of the detail, which its rule of the bus puts under `detail.payload` (ADR 0127). The delivery `sync`
 * reads it the way EventBridge does, for the part of its grammar the subscriptions use: a field and the values it may
 * hold, all fields matching, and `$or` between patterns.
 *
 * @see https://docs.aws.amazon.com/eventbridge/latest/userguide/eb-create-pattern-operators.html
 */
final class PayloadPattern
{
    private const string OR = '$or';

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $pattern
     */
    public static function matches(array $payload, array $pattern): bool
    {
        foreach ($pattern as $field => $expected) {
            $matched = self::OR === $field
                ? array_any((array) $expected, static fn (mixed $alternative): bool => is_array($alternative) && self::matches($payload, $alternative))
                : array_key_exists($field, $payload) && in_array($payload[$field], (array) $expected, true);

            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}
