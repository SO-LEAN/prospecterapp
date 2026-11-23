<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: the recipients that Prospecting has already logged an exchange of the campaign with, before Campaigns processes
 * them from its facts (ADR 0195). It asks Prospecting through its published service
 * `WhichContactsHaveExchangesOfCampaignService`.
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
