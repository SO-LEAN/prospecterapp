<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationExchange;

/**
 * Notes, from the card of an organization, a call, a message or a meeting with the organization itself, or with
 * one of its contacts when `personId` names one (ADR 0076). The channel and the direction are named by the
 * values of `Channel` and `Direction`.
 */
final readonly class NoteOrganizationExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $organizationId,
        public ?string $personId,
        public string $channel,
        public string $direction,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public bool $missed,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
