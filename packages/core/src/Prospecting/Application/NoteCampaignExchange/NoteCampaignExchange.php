<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteCampaignExchange;

/**
 * Notes the exchange a prospector had with a recipient of a started campaign, by the channel of the campaign, kept
 * for the organizations the contact is part of now (ADR 0074, 0145); where it was sent, the `Message-ID` of an e-mail
 * or the conversation of a LinkedIn message, fingerprints it, and an AI notes it once (ADR 0166); `byAnAI` when an AI
 * leading the campaign notes it, acting as the prospector (ADR 0185).
 */
final readonly class NoteCampaignExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $campaignId,
        public string $personId,
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
