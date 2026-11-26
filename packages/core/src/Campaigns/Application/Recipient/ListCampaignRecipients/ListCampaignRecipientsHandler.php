<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\Campaigns\Domain\Port\TaggableContacts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A recipient whose contact PersonalData no longer knows is left out, even before the reaction to their erasure runs.
 * The exception is a recipient that a started campaign keeps as erased: what the team did with them is kept (ADR 0145).
 */
final readonly class ListCampaignRecipientsHandler implements QueryHandler
{
    public function __construct(
        private CampaignSheetRetriever $campaignSheetRetriever,
        private Campaigners $campaigners,
        private TaggableContacts $taggableContacts,
    ) {
    }

    public function __invoke(ListCampaignRecipients $query, ListCampaignRecipientsOutput $output): void
    {
        $accountId = $this->campaigners->accountOf(new MemberId($query->memberId));
        $campaign = $this->campaignSheetRetriever->get($accountId, new CampaignId($query->campaignId));

        $output->present(new CampaignRecipients($campaign->id, $this->shown($campaign, $accountId), $campaign->possibleIntents));
    }

    /**
     * @return array<string, RecipientInSheet> by contact id, in the order added
     */
    private function shown(CampaignSheet $campaign, AccountId $accountId): array
    {
        $contacts = array_map(static fn (int|string $id): ContactId => new ContactId((string) $id), array_keys($campaign->recipients));
        $known = array_flip(array_map(static fn (ContactId $contact): string => $contact->value, $this->taggableContacts->contactsAmong($contacts, $accountId)));

        return array_filter($campaign->recipients, static fn (RecipientInSheet $recipient, int|string $id): bool => $recipient->erased || isset($known[(string) $id]), ARRAY_FILTER_USE_BOTH);
    }
}
