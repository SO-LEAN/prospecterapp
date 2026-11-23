<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Port: the recipients that Prospecting has already logged an exchange of the campaign with, before Campaigns processes
 * them from its facts (ADR 0195). It asks Prospecting through its published service
 * `WhichContactsHaveExchangesOfCampaignService`.
 */
interface CampaignExchanges
{
    /**
     * @param list<ContactId> $recipients
     *
     * @return list<ContactId>
     */
    public function exchangedWith(AccountId $accountId, CampaignId $campaign, array $recipients): array;
}
