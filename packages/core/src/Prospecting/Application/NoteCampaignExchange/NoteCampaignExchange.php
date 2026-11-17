<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteCampaignExchange;

/**
 * Notes the exchange a prospector had with a recipient of a started campaign, by the channel of the campaign, kept
 * for the organizations the contact is part of now (ADR 0074, 0145).
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
    ) {
    }
}
