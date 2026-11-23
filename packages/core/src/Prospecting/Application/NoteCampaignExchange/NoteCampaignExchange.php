<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteCampaignExchange;

/**
 * Logs the exchange a prospector had with a recipient of a started campaign, through the campaign's channel.
 * The exchange is kept for the organizations the contact is part of now (ADR 0074, 0145).
 * Where it was sent fingerprints it: an e-mail's `Message-ID`, or a LinkedIn message's conversation. So an AI logs it
 * only once (ADR 0166). `byAnAI` is set when an AI that leads the campaign logs it, acting as the prospector
 * (ADR 0185).
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
