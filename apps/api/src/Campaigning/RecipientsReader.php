<?php

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
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The recipients of a frozen campaign composed of three contexts: Campaigns keeps them by their identities,
 * PersonalData reads them, Prospecting tells the organizations of each and Campaigns the tags each carries, in one
 * batch each, as the search by tags shows its contacts.
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
        $organizations = $this->organizationsOfContacts->read($reader, $contactIds);
        $tags = $this->tagsOfContacts->read($reader, $contactIds);
        $removable = $this->intentLinks->shows('RemoveRecipient', $campaign->possibleIntents, User::PERSONAL_DATA);

        return new RecipientListResource(
            array_map(fn (PersonCard $contact): RecipientResource => new RecipientResource(
                $this->contactInListResourceFactory->create($contact, $organizations[$contact->id] ?? [], $tags[$contact->id] ?? []),
                $campaign->recipients[$contact->id],
                new RecipientLinksResource($removable ? $this->links->to('api_campaigns_remove_recipient', ['id' => $campaign->id, 'contactId' => $contact->id]) : null),
            ), $this->personsOf($reader, $contactIds)),
            new RecipientListLinksResource($this->links->to('api_campaigns_recipients', ['id' => $campaign->id])),
        );
    }

    /**
     * @param list<string> $contactIds
     *
     * @return list<PersonCard> in their order, those PersonalData no longer reads left out
     */
    private function personsOf(string $reader, array $contactIds): array
    {
        $kept = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($reader, $contactIds), $kept);

        return array_values(array_filter(array_map(static fn (string $id): ?PersonCard => $kept->persons[$id] ?? null, $contactIds)));
    }
}
