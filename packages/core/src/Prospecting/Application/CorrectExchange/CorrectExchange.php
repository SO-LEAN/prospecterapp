<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchange;

/**
 * Corrects what happened in an exchange, all its facts at once. Its contact stays. The values of `Channel` and
 * `Direction` name the channel and the direction. When the contact is forgotten, the prospector may declare the
 * exchange redacted in the same gesture.
 */
final readonly class CorrectExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $channel,
        public string $direction,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public bool $missed,
        public \DateTimeImmutable $executedAt,
        public bool $madeConform = false,
    ) {
    }
}
