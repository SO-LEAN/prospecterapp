<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\FreezeCampaign;

final readonly class FreezeCampaign
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
