<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationContactLinksResource;
use App\Contacts\Resource\OrganizationContactResource;
use App\Contacts\Resource\OrganizationContactsResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * Reads an organization's contacts from two contexts (ADR 0041). Prospecting knows which persons the organization
 * has. PersonalData reads them in one batch, unmasked or masked. The reader leaves an erased person out.
 */
final readonly class OrganizationContactsReader
{
    public function __construct(
        private QueryHandler $consultPersons,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $memberId, OrganizationSheet $organization): OrganizationContactsResource
    {
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($memberId, $organization->contactIds), $persons);
        $contacts = array_values(array_filter(array_map(static fn (string $contactId): ?PersonCard => $persons->persons[$contactId] ?? null, $organization->contactIds)));

        return new OrganizationContactsResource(array_map(fn (PersonCard $contact): OrganizationContactResource => $this->present($contact, $organization), $contacts));
    }

    private function present(PersonCard $contact, OrganizationSheet $organization): OrganizationContactResource
    {
        return new OrganizationContactResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            title: $contact->civility,
            jobTitle: $contact->jobTitle,
            contactDetails: PersonContactDetailsResource::of($contact),
            masked: $contact->masked,
            links: new OrganizationContactLinksResource(
                $this->links->to('api_contacts_show', ['id' => $contact->id]),
                $this->intentLinks->shows('RemoveContact', $organization->possibleIntents, User::PERSONAL_DATA) ? $this->links->to('api_organizations_remove_contact', ['id' => $organization->id, 'personId' => $contact->id]) : null,
            ),
        );
    }
}
