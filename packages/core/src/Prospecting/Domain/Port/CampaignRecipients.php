<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\RecipientInCampaign;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: a contact as one of the account's campaigns keeps them. Campaigns keeps the campaigns, and Prospecting never
 * sees them. The API answers through an anticorruption adapter (ADR 0045, 0145).
 */
interface CampaignRecipients
{
    /**
     * Returns null when the account has no campaign with this identity.
     */
    public function recipientIn(CampaignId $campaign, PersonId $contact, AccountId $accountId): ?RecipientInCampaign;
}
