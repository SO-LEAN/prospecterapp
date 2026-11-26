<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * A part of the compositions that show contacts in a list (ADR 0217). PersonalData reads each contact's card, as the
 * reader may read it. Prospecting gives their organizations and Campaigns the tags they carry. Each context answers its
 * own query, in one batch, and the parts are joined by the contacts' ids.
 */
final readonly class ContactsInList
{
    public function __construct(
        private QueryHandler $consultPersons,
        private OrganizationsOfContacts $organizationsOfContacts,
        private TagsOfContacts $tagsOfContacts,
        private ContactInListResourceFactory $contactInListResourceFactory,
    ) {
    }

    /**
     * @param list<string> $contactIds
     *
     * @return array<string, ContactInListResource> by id, the contacts PersonalData gives back
     */
    public function of(string $reader, array $contactIds): array
    {
        $kept = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, $contactIds), $kept);

        return $this->ofCards($reader, array_values($kept->persons));
    }

    /**
     * @param list<PersonCard> $cards
     *
     * @return array<string, ContactInListResource> by id, in the cards' order
     */
    public function ofCards(string $reader, array $cards): array
    {
        $contactIds = array_map(static fn (PersonCard $card): string => $card->id, $cards);
        $organizations = $this->organizationsOfContacts->read($reader, $contactIds);
        $tags = $this->tagsOfContacts->read($reader, $contactIds);
        $contacts = [];

        foreach ($cards as $card) {
            $contacts[$card->id] = $this->contactInListResourceFactory->create($card, $organizations[$card->id] ?? [], $tags[$card->id] ?? []);
        }

        return $contacts;
    }
}
