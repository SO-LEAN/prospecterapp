<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Typed;

/**
 * The payloads that a subscription listens to (`payload` in `config/subscriptions.json`). It is an EventBridge
 * pattern on the detail's payload, which the subscription's bus rule puts under `detail.payload` (ADR 0127). The
 * `sync` delivery reads it the way EventBridge does, for the part of the grammar that the subscriptions use: a field
 * and the values it may hold, all fields matching, and `$or` between patterns.
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
                ? array_any((array) $expected, static fn (mixed $alternative): bool => is_array($alternative) && self::matches($payload, Typed::document($alternative)))
                : array_key_exists($field, $payload) && in_array($payload[$field], (array) $expected, true);

            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}
