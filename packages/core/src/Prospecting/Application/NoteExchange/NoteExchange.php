<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteExchange;

/**
 * Notes a call, a message or a meeting with a contact, kept for the organizations the contact is part of
 * now (ADR 0074). The channel and the direction are named by the values of `Channel` and `Direction`.
 */
final readonly class NoteExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $personId,
        public string $channel,
        public string $direction,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public bool $missed,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
