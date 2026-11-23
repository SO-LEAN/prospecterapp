<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\CampaignExchanges;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\KeptContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaign;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaignService;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * The API's anticorruption layer in the scenarios (ADR 0045, 0195): the recipients with whom Prospecting logged an
 * exchange of their campaign.
 */
final readonly class ProspectingCampaignExchanges implements CampaignExchanges
{
    public function __construct(private WhichContactsHaveExchangesOfCampaignService $whichContactsHaveExchangesOfCampaignService)
    {
    }

    /**
     * @param list<ContactId> $recipients
     *
     * @return list<ContactId>
     */
    public function exchangedWith(AccountId $accountId, CampaignId $campaign, array $recipients): array
    {
        $kept = new KeptContacts();
        ($this->whichContactsHaveExchangesOfCampaignService)(new WhichContactsHaveExchangesOfCampaign($accountId->value, $campaign->value, array_map(static fn (ContactId $recipient): string => $recipient->value, $recipients)), $kept);

        return array_map(static fn (string $contact): ContactId => new ContactId($contact), $kept->contactIds);
    }
}
