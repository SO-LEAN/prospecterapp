<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Port\CampaignExchanges;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\KeptContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaign;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaignService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0195): the recipients with whom Prospecting logged an exchange of their campaign,
 * before Campaigns' reaction processes them.
 */
final readonly class ProspectingCampaignExchanges implements CampaignExchanges
{
    public function __construct(private WhichContactsHaveExchangesOfCampaignService $whichContactsHaveExchangesOfCampaignService)
    {
    }

    /**
     * @param list<PersonId> $recipients
     *
     * @return list<PersonId>
     */
    public function exchangedWith(AccountId $accountId, CampaignId $campaign, array $recipients): array
    {
        $kept = new KeptContacts();
        ($this->whichContactsHaveExchangesOfCampaignService)(new WhichContactsHaveExchangesOfCampaign($accountId->value, $campaign->value, array_map(static fn (PersonId $recipient): string => $recipient->value, $recipients)), $kept);

        return array_map(static fn (string $contact): PersonId => new PersonId($contact), $kept->contactIds);
    }
}
