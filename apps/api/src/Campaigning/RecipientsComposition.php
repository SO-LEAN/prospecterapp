<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\RecipientListResource;
use App\Campaigning\Resource\RecipientResource;
use App\Contacts\ContactsInList;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients\ListCampaignRecipients;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes a campaign's recipients (ADR 0217). Campaigns lists them, with their status and links. PersonalData,
 * Prospecting and Campaigns then give each one's contact in a list, joined by the contact's id. A recipient that a
 * started campaign keeps as erased has no contact.
 */
final readonly class RecipientsComposition
{
    public function __construct(
        private QueryHandler $listCampaignRecipients,
        private ContactsInList $contactsInList,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, string $campaignId): RecipientListResource
    {
        $presenter = new RecipientListPresenter($this->links, $this->intentLinks);
        $this->listCampaignRecipients->__invoke(new ListCampaignRecipients($reader, $campaignId), $presenter);
        $recipients = $presenter->recipients->recipients;
        $contactIds = array_map(strval(...), array_keys($recipients));
        $contacts = $this->contactsInList->of($reader, $contactIds);

        return new RecipientListResource(
            array_map(static fn (string $contactId, RecipientInSheet $recipient): RecipientResource => new RecipientResource(
                $contacts[$contactId] ?? null,
                $recipient->status,
                $recipient->missed,
                $recipient->reason,
                $presenter->links[$contactId],
            ), $contactIds, array_values($recipients)),
            $presenter->listLinks,
        );
    }
}
