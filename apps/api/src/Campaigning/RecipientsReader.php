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
 * batch each, as the search by tags shows its contacts. A recipient PersonalData no longer reads is left out, but one
 * a started campaign keeps erased, shown without its contact.
 */
final readonly class RecipientsReader
{
    private const string ERASED = 'erased';

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
        $shown = array_filter($contactIds, static fn (string $id): bool => isset($persons[$id]) || self::ERASED === $campaign->recipients[$id]->status);

        return new RecipientListResource(
            array_values(array_map(fn (string $id): RecipientResource => new RecipientResource(
                isset($persons[$id]) ? $this->contactInListResourceFactory->create($persons[$id], $organizations[$id] ?? [], $tags[$id] ?? []) : null,
                $campaign->recipients[$id]->status,
                $campaign->recipients[$id]->missed,
                $campaign->recipients[$id]->reason,
                new RecipientLinksResource($removable ? $this->links->to('api_campaigns_remove_recipient', ['id' => $campaign->id, 'contactId' => $id]) : null),
            ), $shown)),
            new RecipientListLinksResource($this->links->to('api_campaigns_recipients', ['id' => $campaign->id])),
        );
    }

    /**
     * @param list<string> $contactIds
     *
     * @return array<string, PersonCard> those PersonalData reads, by identity
     */
    private function personsOf(string $reader, array $contactIds): array
    {
        $kept = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($reader, $contactIds), $kept);

        return $kept->persons;
    }
}
