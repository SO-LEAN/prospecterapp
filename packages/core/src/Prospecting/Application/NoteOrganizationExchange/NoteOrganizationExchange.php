<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationExchange;

/**
 * Notes, from the card of an organization, a call, a message or a meeting with the organization itself, or with
 * one of its contacts when `personId` names one (ADR 0076). The channel and the direction are named by the
 * values of `Channel` and `Direction`. `byAnAI` when an AI notes it, acting as the prospector.
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
        public ?string $messageId = null,
        public ?string $linkedInConversation = null,
        public bool $noteOnce = false,
        public bool $byAnAI = false,
    ) {
    }
}
