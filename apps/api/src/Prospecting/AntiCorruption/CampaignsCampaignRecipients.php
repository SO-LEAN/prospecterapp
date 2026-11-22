<?php

declare(strict_types=1);

namespace App\Prospecting\AntiCorruption;

use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\KeptPendingRecipients;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\WhichRecipientsArePending;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\WhichRecipientsArePendingService;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\RecipientInCampaign;
use Prospecter\Core\Prospecting\Domain\Port\CampaignRecipients;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0145): Prospecting notes the exchange of a
 * recipient, Campaigns tells whether the contact is one still to handle, asked about a set of one.
 */
final readonly class CampaignsCampaignRecipients implements CampaignRecipients
{
    public function __construct(private WhichRecipientsArePendingService $whichRecipientsArePendingService)
    {
    }

    public function recipientIn(CampaignId $campaign, PersonId $contact, AccountId $accountId): ?RecipientInCampaign
    {
        $kept = new KeptPendingRecipients();
        ($this->whichRecipientsArePendingService)(new WhichRecipientsArePending($accountId->value, $campaign->value, [$contact->value]), $kept);
        $answered = $kept->campaign;

        return null === $answered ? null : new RecipientInCampaign(
            $campaign,
            $contact,
            Channel::from($answered->channel),
            $answered->started,
            in_array($contact->value, $answered->recipientIds, true),
            in_array($contact->value, $answered->pendingIds, true),
        );
    }
}
