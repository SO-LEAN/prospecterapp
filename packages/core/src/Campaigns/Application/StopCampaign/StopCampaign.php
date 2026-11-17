<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\StopCampaign;

final readonly class StopCampaign
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
