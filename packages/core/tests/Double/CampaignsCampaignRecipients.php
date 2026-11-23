<?php

declare(strict_types=1);

namespace Tests\Core\Double;

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
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0145). Prospecting logs a recipient's exchange.
 * Campaigns tells whether the contact is a recipient that still needs handling. It is asked about a set of one.
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
