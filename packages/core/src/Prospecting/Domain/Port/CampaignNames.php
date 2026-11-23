<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * Port: the names of the account's campaigns, as Campaigns names them now. An exchange keeps only its campaign's
 * identity (ADR 0145).
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
