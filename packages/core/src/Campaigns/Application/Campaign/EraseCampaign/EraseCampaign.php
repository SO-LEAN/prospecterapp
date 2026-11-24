<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\EraseCampaign;

final readonly class EraseCampaign
{
    public function __construct(public string $campaignId)
    {
    }
}
