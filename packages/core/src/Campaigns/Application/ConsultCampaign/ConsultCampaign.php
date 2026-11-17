<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultCampaign;

final readonly class ConsultCampaign
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
    ) {
    }
}
