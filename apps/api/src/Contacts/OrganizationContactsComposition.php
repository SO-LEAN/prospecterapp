<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationContactResource;
use App\Contacts\Resource\OrganizationContactsResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use App\Shared\Resource\ExternalLinkResource;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\ListOrganizationContacts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes an organization's contacts (ADR 0217). Prospecting lists them, with their links, and has left out a contact
 * PersonalData no longer knows. PersonalData reads each one as the reader may read them, unmasked or masked, joined by
 * the contact's id. A contact erased between the two reads has no card to show, and is skipped.
 */
final readonly class OrganizationContactsComposition
{
    public function __construct(
        private QueryHandler $listOrganizationContacts,
        private PersonsOfAView $personsOfAView,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $reader, string $organizationId): OrganizationContactsResource
    {
        $presenter = new OrganizationContactsPresenter($this->links, $this->intentLinks);
        $this->listOrganizationContacts->__invoke(new ListOrganizationContacts($organizationId), $presenter);
        $persons = $this->personsOfAView->shownTo($reader, array_keys($presenter->links));

        return new OrganizationContactsResource(array_values(array_map(
            static fn (PersonCard $contact): OrganizationContactResource => new OrganizationContactResource(
                id: $contact->id,
                name: new PersonNameResource($contact->firstName, $contact->lastName),
                title: $contact->civility,
                jobTitle: $contact->jobTitle,
                contactDetails: new PersonContactDetailsResource($contact->email, $contact->phoneNumber, $contact->language, ExternalLinkResource::allOf($contact->links)),
                masked: $contact->masked,
                links: $presenter->links[$contact->id],
            ),
            array_filter(array_map($persons->contact(...), array_keys($presenter->links))),
        )));
    }
}
