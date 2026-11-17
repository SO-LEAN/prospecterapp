<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\RecipientInCampaign;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: a contact as a campaign of the account keeps them. Campaigns keeps the campaigns, Prospecting never sees
 * them; the API answers through an anticorruption adapter (ADR 0045, 0145).
 */
interface CampaignRecipients
{
    /**
     * None when the account has no campaign of this identity.
     */
    public function recipientIn(CampaignId $campaign, PersonId $contact, AccountId $accountId): ?RecipientInCampaign;
}
