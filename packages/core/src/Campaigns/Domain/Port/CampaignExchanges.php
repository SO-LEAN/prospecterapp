<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: which recipients Prospecting already noted an exchange of the campaign with, asked of it through its published
 * service `WhichContactsHaveExchangesOfCampaignService`, before Campaigns processes them from its facts (ADR 0195).
 */
interface CampaignExchanges
{
    /**
     * @param list<PersonId> $recipients
     *
     * @return list<PersonId>
     */
    public function exchangedWith(AccountId $accountId, CampaignId $campaign, array $recipients): array;
}
