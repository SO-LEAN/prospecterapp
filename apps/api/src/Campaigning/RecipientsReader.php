<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\RecipientLinksResource;
use App\Campaigning\Resource\RecipientListLinksResource;
use App\Campaigning\Resource\RecipientListResource;
use App\Campaigning\Resource\RecipientResource;
use App\Contacts\ContactInListResourceFactory;
use App\Contacts\OrganizationsOfContacts;
use App\Contacts\TagsOfContacts;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes a frozen campaign's recipients from three contexts, in one batch each, as the tag search shows its contacts.
 * Campaigns keeps the recipients by id, and PersonalData reads them. Prospecting gives each recipient's organizations,
 * and Campaigns gives the tags each one carries. A recipient that PersonalData no longer reads is left out. The
 * exception is a recipient that a started campaign keeps as erased: it is shown without its contact, with what the team
 * did with them. In a started campaign, a recipient to handle links to the exchange to log for them and to their
 * dismissal (ADR 0145). A processed recipient links to the exchange logged for them (ADR 0152).
 */
final readonly class RecipientsReader
{
    public function __construct(
        private QueryHandler $consultPersons,
        private OrganizationsOfContacts $organizationsOfContacts,
        private TagsOfContacts $tagsOfContacts,
        private ContactInListResourceFactory $contactInListResourceFactory,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, CampaignSheet $campaign): RecipientListResource
    {
        $contactIds = array_map(strval(...), array_keys($campaign->recipients));
        $persons = $this->personsOf($reader, $contactIds);
        $read = array_map(strval(...), array_keys($persons));
        $organizations = $this->organizationsOfContacts->read($reader, $read);
        $tags = $this->tagsOfContacts->read($reader, $read);
        $removable = $this->intentLinks->shows('RemoveRecipient', $campaign->possibleIntents, User::PERSONAL_DATA);
        $running = $this->intentLinks->shows('RunCampaign', $campaign->possibleIntents, User::PERSONAL_DATA);
        $shown = array_filter($contactIds, static fn (string $id): bool => isset($persons[$id]) || $campaign->recipients[$id]->erased);

        return new RecipientListResource(
            array_values(array_map(fn (string $id): RecipientResource => new RecipientResource(
                isset($persons[$id]) ? $this->contactInListResourceFactory->create($persons[$id], $organizations[$id] ?? [], $tags[$id] ?? []) : null,
                $campaign->recipients[$id]->status,
                $campaign->recipients[$id]->missed,
                $campaign->recipients[$id]->reason,
                $this->linksOf($campaign, $id, $removable, $running && $campaign->recipients[$id]->pending),
            ), $shown)),
            new RecipientListLinksResource($this->links->to('api_campaigns_recipients', ['id' => $campaign->id])),
        );
    }

    private function linksOf(CampaignSheet $campaign, string $contactId, bool $removable, bool $toHandle): RecipientLinksResource
    {
        $recipient = ['id' => $campaign->id, 'contactId' => $contactId];

        return new RecipientLinksResource(
            remove: $removable ? $this->links->to('api_campaigns_remove_recipient', $recipient) : null,
            noteExchange: $toHandle ? $this->links->to('api_campaigns_note_exchange', $recipient) : null,
            dismiss: $toHandle ? $this->links->to('api_campaigns_dismiss_recipient', $recipient) : null,
            exchange: $this->exchangeOf($campaign->recipients[$contactId]),
        );
    }

    private function exchangeOf(RecipientInSheet $recipient): ?LinkResource
    {
        return null === $recipient->exchangeId ? null : $this->links->to('api_exchanges_show', ['id' => $recipient->exchangeId]);
    }

    /**
     * @param list<string> $contactIds
     *
     * @return array<string, PersonCard> the people that PersonalData reads, by id
     */
    private function personsOf(string $reader, array $contactIds): array
    {
        $kept = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, $contactIds), $kept);

        return $kept->persons;
    }
}
