<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\DiscardCampaign;

final readonly class DiscardCampaign
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
