<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * Port: the names of campaigns of the account, as Campaigns names them now, an exchange keeping only the identity of
 * its campaign (ADR 0145).
 */
interface CampaignNames
{
    /**
     * @param list<CampaignId> $campaigns
     *
     * @return array<string, string> by identity, a campaign the account no longer has absent
     */
    public function of(array $campaigns, AccountId $accountId): array;
}
