<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\NoteOrganizationExchange;

/**
 * Logs a call, a message or a meeting from an organization's card. The exchange is with the organization itself, or
 * with one of its contacts when `personId` names one (ADR 0076). The channel and the direction take the values of
 * `Channel` and `Direction`. `byAnAI` is set when an AI logs it, acting as the prospector.
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
